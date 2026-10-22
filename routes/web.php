<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jessecruz\SimpleBlog\Http\Controllers\BlogController;
use Jessecruz\SimpleBlog\Http\Controllers\FeedController;
use Jessecruz\SimpleBlog\Http\Controllers\SitemapController;
use Jessecruz\SimpleBlog\Http\Middleware\SetBlogLocale;
use Jessecruz\SimpleBlog\Livewire\Admin\CategoryIndex;
use Jessecruz\SimpleBlog\Livewire\Admin\PostForm;
use Jessecruz\SimpleBlog\Livewire\Admin\PostIndex;

Route::prefix(config('blog.route_prefix'))
    ->middleware([...config('blog.public_middleware'), SetBlogLocale::class])
    ->name('blog.')
    ->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/category/{category}', [BlogController::class, 'category'])->name('category');

        // Before /{post}: "feed" is a reserved post slug (Post::RESERVED_SLUGS).
        if (config('blog.feed.enabled', true)) {
            Route::get('/feed', FeedController::class)->name('feed');
        }

        if (config('blog.sitemap.enabled', true)) {
            Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
        }

        Route::get('/{post}', [BlogController::class, 'show'])->name('show');
    });

Route::prefix(config('blog.admin_route_prefix'))
    ->middleware([...config('blog.admin_middleware'), SetBlogLocale::class])
    ->name('blog.admin.')
    ->group(function () {
        Route::get('/', PostIndex::class)->name('index');
        Route::get('/create', PostForm::class)->name('create');
        Route::get('/{post}/edit', PostForm::class)->name('edit');
        Route::get('/{post}/preview', [BlogController::class, 'preview'])->name('preview');
        Route::get('/categories', CategoryIndex::class)->name('categories');
    });
