<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog;

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
            ->hasViews('blog')
            ->hasRoute('web')
            ->hasMigrations([
                'create_blog_categories_table',
                'create_blog_posts_table',
            ]);
    }

    public function packageBooted(): void
    {
        Livewire::component('blog::admin.post-index', PostIndex::class);
        Livewire::component('blog::admin.post-form', PostForm::class);
        Livewire::component('blog::admin.category-index', CategoryIndex::class);
    }
}
