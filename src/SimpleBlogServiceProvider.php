<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Jessecruz\SimpleBlog\Commands\AnnounceCommand;
use Jessecruz\SimpleBlog\Commands\InstallCommand;
use Jessecruz\SimpleBlog\Events\PostPublished;
use Jessecruz\SimpleBlog\Http\Middleware\SetBlogLocale;
use Jessecruz\SimpleBlog\Listeners\SendPostWebhooks;
use Jessecruz\SimpleBlog\Livewire\Admin\CategoryIndex;
use Jessecruz\SimpleBlog\Livewire\Admin\PostForm;
use Jessecruz\SimpleBlog\Livewire\Admin\PostIndex;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SimpleBlogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('simple-blog')
            ->hasConfigFile('blog')
            ->hasRoute('web')
            ->hasMigrations([
                'create_blog_categories_table',
                'create_blog_posts_table',
                'add_announced_at_to_blog_posts_table',
            ])
            ->hasCommands([InstallCommand::class, AnnounceCommand::class]);
    }

    public function packageBooted(): void
    {
        Livewire::component('blog.admin.post-index', PostIndex::class);
        Livewire::component('blog.admin.post-form', PostForm::class);
        Livewire::component('blog.admin.category-index', CategoryIndex::class);

        Livewire::addPersistentMiddleware([SetBlogLocale::class]);

        Event::listen(PostPublished::class, SendPostWebhooks::class);
        $this->schedulePostAnnouncements();

        // Opt-in: hosts that don't enable the API get no extra routes.
        if (config('blog.api.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blog');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'blog');

        $this->registerViewPublishing();
        $this->registerLangPublishing();
    }

    /**
     * Publish tags for views — all prefixed with the package name "simple-blog":
     *
     *   --tag="simple-blog-views"          → everything (public + admin + layouts)
     *   --tag="simple-blog-views-public"   → public templates, share component, feed, sitemap, public layout
     *   --tag="simple-blog-views-admin"    → Livewire admin templates + admin layout
     *   --tag="simple-blog-views-layouts"  → just the public + admin layouts
     */
    private function registerViewPublishing(): void
    {
        $views = __DIR__.'/../resources/views';
        $target = fn (string $path) => resource_path('views/vendor/blog/'.$path);

        $this->publishes([
            $views => $target(''),
        ], 'simple-blog-views');

        $this->publishes([
            $views.'/index.blade.php' => $target('index.blade.php'),
            $views.'/show.blade.php' => $target('show.blade.php'),
            $views.'/components/share.blade.php' => $target('components/share.blade.php'),
            $views.'/feed.blade.php' => $target('feed.blade.php'),
            $views.'/sitemap.blade.php' => $target('sitemap.blade.php'),
            $views.'/layouts/public.blade.php' => $target('layouts/public.blade.php'),
        ], 'simple-blog-views-public');

        $this->publishes([
            $views.'/livewire/admin' => $target('livewire/admin'),
            $views.'/layouts/admin.blade.php' => $target('layouts/admin.blade.php'),
        ], 'simple-blog-views-admin');

        $this->publishes([
            $views.'/layouts/public.blade.php' => $target('layouts/public.blade.php'),
            $views.'/layouts/admin.blade.php' => $target('layouts/admin.blade.php'),
        ], 'simple-blog-views-layouts');
    }

    /**
     * Runs simple-blog:announce on the host's scheduler, at the cron
     * expression in `blog.publishing.schedule` (null → not scheduled).
     */
    private function schedulePostAnnouncements(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $expression = config('blog.publishing.schedule');

            if (is_string($expression) && $expression !== '') {
                $schedule->command(AnnounceCommand::class)->cron($expression);
            }
        });
    }

    private function registerLangPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/blog'),
        ], 'simple-blog-lang');
    }
}
