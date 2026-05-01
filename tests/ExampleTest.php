<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

it('boots with the package config and creates blog tables', function () {
    expect(config('blog.route_prefix'))->toBe('blog')
        ->and(Schema::hasTable('blog_posts'))->toBeTrue()
        ->and(Schema::hasTable('blog_categories'))->toBeTrue();
});

it('persists posts and categories through the package models', function () {
    $category = PostCategory::create([
        'slug' => 'gestao',
        'name' => 'Gestão',
    ]);

    $post = Post::create([
        'slug' => 'meu-post',
        'title' => 'Meu Post',
        'excerpt' => 'Resumo',
        'body' => '## Olá',
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ]);

    expect($post->category->is($category))->toBeTrue()
        ->and($post->isPublished())->toBeTrue()
        ->and(Post::published()->count())->toBe(1);
});

it('renders body markdown with html escaped by default', function () {
    $category = PostCategory::create(['slug' => 'c', 'name' => 'C']);
    $post = Post::create([
        'slug' => 'p',
        'title' => 'P',
        'excerpt' => 'e',
        'body' => '<script>alert(1)</script>',
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ]);

    expect($post->renderedBody())->not->toContain('<script>');
});

it('registers public and admin routes from the package', function () {
    $names = collect(Route::getRoutes()->getRoutesByName())->keys();

    expect($names)->toContain('blog.index', 'blog.show', 'blog.category', 'blog.admin.index', 'blog.admin.create', 'blog.admin.preview');
});
