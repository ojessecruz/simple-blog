<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Jessecruz\SimpleBlog\Events\PostPublished;
use Jessecruz\SimpleBlog\Models\Post;
use Throwable;

final class AnnounceCommand extends Command
{
    protected $signature = 'simple-blog:announce';

    protected $description = 'Dispatch PostPublished for posts that went live since the last run';

    public function handle(): int
    {
        // Upgraded without migrating: say so once per run instead of a SQL error every minute.
        if (! Schema::hasColumn((new Post)->getTable(), 'announced_at')) {
            $this->components->error('Missing blog_posts.announced_at. Publish and run the new migration: php artisan vendor:publish --tag=simple-blog-migrations && php artisan migrate');

            return self::FAILURE;
        }

        $window = config('blog.publishing.announce_window', 24);
        $cutoff = is_numeric($window) ? now()->subHours((int) $window) : null;
        $announced = 0;
        $skipped = 0;

        // By id, not offset: each claimed post leaves the whereNull filter.
        Post::published()->whereNull('announced_at')->lazyById(100)->each(
            function (Post $post) use ($cutoff, &$announced, &$skipped): void {
                // Claim first, so overlapping runs never announce a post twice.
                // Through the base query so updated_at (the sitemap's lastmod) is untouched.
                $claimed = Post::query()->whereKey($post->getKey())->whereNull('announced_at')
                    ->toBase()->update(['announced_at' => now()]);

                if ($claimed === 0) {
                    return;
                }

                // Imports, backdated posts and long scheduler outages: marked, not announced.
                if ($cutoff !== null && $post->published_at?->lt($cutoff)) {
                    $skipped++;

                    return;
                }

                try {
                    PostPublished::dispatch($post->refresh()->load('category', 'author'));
                    $announced++;
                } catch (Throwable $e) {
                    // At most once: a failing listener or webhook doesn't block the other posts.
                    report($e);
                }
            },
        );

        $this->components->info("Announced {$announced} post(s), skipped {$skipped} outside the announce window.");

        return self::SUCCESS;
    }
}
