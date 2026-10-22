<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Jessecruz\SimpleBlog\Events\PostPublished;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

beforeEach(function () {
    $this->category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
});

afterEach(fn () => Carbon::setTestNow());

function announcedPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'slug' => 'p-'.uniqid(),
        'title' => 'Post',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => PostCategory::first()->id,
        'published_at' => now()->subMinute(),
    ], $attributes));
}

it('announces each post that went live exactly once', function () {
    Event::fake([PostPublished::class]);
    $live = announcedPost(['slug' => 'live']);
    announcedPost(['slug' => 'draft', 'published_at' => null]);
    announcedPost(['slug' => 'later', 'published_at' => now()->addHour()]);

    $this->artisan('simple-blog:announce')->assertSuccessful();
    $this->artisan('simple-blog:announce')->assertSuccessful();

    Event::assertDispatchedTimes(PostPublished::class, 1);
    Event::assertDispatched(PostPublished::class, fn (PostPublished $event) => $event->post->is($live)
        && $event->post->relationLoaded('category'));

    expect($live->fresh()->announced_at)->not->toBeNull()
        ->and(Post::whereNull('announced_at')->pluck('slug')->sort()->values()->all())->toBe(['draft', 'later']);
});

it('announces a scheduled post once its date arrives', function () {
    Event::fake([PostPublished::class]);
    Carbon::setTestNow('2026-10-22 09:00:00');
    announcedPost(['published_at' => '2026-10-22 10:00:00']);

    $this->artisan('simple-blog:announce');
    Event::assertNotDispatched(PostPublished::class);

    Carbon::setTestNow('2026-10-22 10:00:30');
    $this->artisan('simple-blog:announce');
    Event::assertDispatchedTimes(PostPublished::class, 1);
});

it('marks posts that went live before the announce window without announcing them', function () {
    Event::fake([PostPublished::class]);
    $imported = announcedPost(['published_at' => now()->subDays(3)]);

    $this->artisan('simple-blog:announce')
        ->expectsOutputToContain('Announced 0 post(s), skipped 1')
        ->assertSuccessful();

    Event::assertNotDispatched(PostPublished::class);
    expect($imported->fresh()->announced_at)->not->toBeNull();

    config(['blog.publishing.announce_window' => null]);
    announcedPost(['published_at' => now()->subDays(3)]);

    $this->artisan('simple-blog:announce');
    Event::assertDispatchedTimes(PostPublished::class, 1);
});

it('does not touch updated_at when claiming a post', function () {
    Event::fake([PostPublished::class]);
    Carbon::setTestNow('2026-10-01 10:00:00');
    $post = announcedPost(['published_at' => '2026-10-01 09:59:00']);

    Carbon::setTestNow('2026-10-01 10:01:00');
    $this->artisan('simple-blog:announce');

    expect($post->fresh()->updated_at->toDateTimeString())->toBe('2026-10-01 10:00:00')
        ->and($post->fresh()->announced_at->toDateTimeString())->toBe('2026-10-01 10:01:00');
});

it('keeps announcing other posts when a listener fails', function () {
    $first = announcedPost(['published_at' => now()->subMinutes(2)]);
    $second = announcedPost(['published_at' => now()->subMinute()]);
    $seen = [];
    Event::listen(PostPublished::class, function (PostPublished $event) use ($first, &$seen) {
        $seen[] = $event->post->id;

        if ($event->post->is($first)) {
            throw new RuntimeException('listener failed');
        }
    });

    $this->artisan('simple-blog:announce')->assertSuccessful();

    expect($seen)->toBe([$first->id, $second->id])
        ->and($first->fresh()->announced_at)->not->toBeNull();
});

it('posts a signed JSON webhook to every configured URL', function () {
    Http::fake();
    config([
        'blog.publishing.webhooks' => ['https://hooks.example.com/a', 'https://hooks.example.com/b'],
        'blog.publishing.webhook_secret' => 'shh',
    ]);
    $post = announcedPost(['slug' => 'novo-post', 'title' => 'Novo post']);

    $this->artisan('simple-blog:announce')->assertSuccessful();

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request) use ($post) {
        return $request->url() === 'https://hooks.example.com/a'
            && $request->method() === 'POST'
            && $request->header('X-Simple-Blog-Event') === ['post.published']
            && $request->header('X-Simple-Blog-Signature') === ['sha256='.hash_hmac('sha256', $request->body(), 'shh')]
            && $request['event'] === 'post.published'
            && $request['post']['slug'] === 'novo-post'
            && $request['post']['url'] === $post->url()
            && $request['post']['status'] === 'published';
    });
});

it('sends unsigned webhooks without a secret, and none without URLs', function () {
    Http::fake();
    announcedPost();

    $this->artisan('simple-blog:announce');
    Http::assertNothingSent();

    config(['blog.publishing.webhooks' => ['https://hooks.example.com/a']]);
    announcedPost();

    $this->artisan('simple-blog:announce');
    Http::assertSent(fn (Request $request) => ! $request->hasHeader('X-Simple-Blog-Signature'));
});

it('does not stop on a failing webhook', function () {
    Http::fake(['*' => Http::response('down', 500)]);
    config(['blog.publishing.webhooks' => ['https://hooks.example.com/a']]);
    announcedPost(['published_at' => now()->subMinutes(2)]);
    announcedPost(['published_at' => now()->subMinute()]);

    $this->artisan('simple-blog:announce')->assertSuccessful();

    Http::assertSentCount(2);
    expect(Post::whereNull('announced_at')->count())->toBe(0);
});

it('schedules the command at the configured cron expression', function () {
    $scheduled = fn () => collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'simple-blog:announce'));

    expect($scheduled()->first()?->expression)->toBe('* * * * *');

    $this->bootConfig = ['publishing.schedule' => null];
    $this->refreshApplication();

    expect($scheduled())->toBeEmpty();
});

it('backfills announced_at for posts already live when migrating', function () {
    $migration = include __DIR__.'/../../database/migrations/add_announced_at_to_blog_posts_table.php.stub';
    $migration->down();

    $insert = fn (string $slug, ?string $publishedAt) => DB::table('blog_posts')->insert([
        'slug' => $slug, 'title' => $slug, 'excerpt' => 'r', 'body' => 'c',
        'blog_category_id' => $this->category->id, 'published_at' => $publishedAt,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $insert('old', '2026-01-10 08:00:00');
    $insert('scheduled', now()->addDay()->toDateTimeString());
    $insert('draft', null);

    $migration->up();

    expect(DB::table('blog_posts')->pluck('announced_at', 'slug')->all())->toBe([
        'old' => '2026-01-10 08:00:00',
        'scheduled' => null,
        'draft' => null,
    ]);
});

it('explains the missing migration instead of failing with a SQL error', function () {
    (include __DIR__.'/../../database/migrations/add_announced_at_to_blog_posts_table.php.stub')->down();

    $this->artisan('simple-blog:announce')
        ->expectsOutputToContain('vendor:publish --tag=simple-blog-migrations')
        ->assertFailed();
});
