<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

it('renders author fallbacks when author_model is not configured', function () {
    config()->set('blog.author_model', null);

    $category = PostCategory::create(['name' => 'News', 'slug' => 'news']);

    $post = Post::create([
        'title' => 'Hello',
        'slug' => 'hello',
        'excerpt' => 'Hi',
        'body' => 'Body',
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ]);

    expect($post->authorName())->toBe('Team')
        ->and($post->authorAvatarUrl())->toBeNull();

    $this->get(route('blog.index'))->assertOk();
    $this->get(route('blog.show', $post))->assertOk();
});
