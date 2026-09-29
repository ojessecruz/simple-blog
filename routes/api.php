<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jessecruz\SimpleBlog\Http\Controllers\Api\CategoryController;
use Jessecruz\SimpleBlog\Http\Controllers\Api\PostController;

Route::prefix(config('blog.api.prefix'))
    ->middleware(config('blog.api.middleware'))
    ->name('blog.api.')
    ->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
        Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    });
