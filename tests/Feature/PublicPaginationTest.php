<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

/**
 * Creates $count published posts, "Post 1" being the newest.
 */
function paginatedPosts(int $count, ?PostCategory $category = null): void
{
    $category ??= PostCategory::firstOrCreate(['slug' => 'g'], ['name' => 'Gestão']);

    foreach (range(1, $count) as $i) {
        Post::create([
            'slug' => "post-{$category->slug}-{$i}",
            'title' => "Post {$i}",
            'excerpt' => 'r',
            'body' => 'c',
            'blog_category_id' => $category->id,
            'published_at' => now()->subHours($i),
        ]);
    }
}

it('paginates the index with per_page', function () {
    config(['blog.per_page' => 2, 'blog.locale' => 'pt_BR']);
    paginatedPosts(3);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeInOrder(['Post 1', 'Post 2'])
        ->assertDontSee('Post 3')
        ->assertSee('Página 1 de 2')
        ->assertSee('href="'.route('blog.index', ['page' => 2]).'" rel="next"', false)
        ->assertDontSee('rel="prev"', false);

    $this->get(route('blog.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Post 3')
        ->assertDontSee('Post 2')
        ->assertSee('Página 2 de 2')
        ->assertSee('rel="prev"', false)
        ->assertDontSee('rel="next"', false);
});

it('paginates category pages within the category', function () {
    config(['blog.per_page' => 2]);
    $alpha = PostCategory::create(['slug' => 'alpha', 'name' => 'Alpha']);
    $beta = PostCategory::create(['slug' => 'beta', 'name' => 'Beta']);
    paginatedPosts(3, $alpha);
    paginatedPosts(1, $beta);

    $this->get(route('blog.category', ['category' => $alpha, 'page' => 2]))
        ->assertOk()
        ->assertSee('post-alpha-3', false)
        ->assertDontSee('post-beta-1', false);
});

it('renders no pagination when everything fits on one page', function () {
    paginatedPosts(3);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Post 3')
        ->assertDontSee('rel="next"', false);
});

it('returns 404 for pages past the last one, but not for an empty blog', function () {
    config(['blog.per_page' => 2]);

    $this->get(route('blog.index'))->assertOk();

    paginatedPosts(3);

    $this->get(route('blog.index', ['page' => 3]))->assertNotFound();
});

it('orders posts published at the same time by newest id, so pages never overlap', function () {
    config(['blog.per_page' => 1]);
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $publishedAt = now()->subDay();
    Post::create(['slug' => 'first', 'title' => 'First', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => $publishedAt]);
    Post::create(['slug' => 'second', 'title' => 'Second', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => $publishedAt]);

    $this->get(route('blog.index'))->assertSee('Second')->assertDontSee('First');
    $this->get(route('blog.index', ['page' => 2]))->assertSee('First')->assertDontSee('Second');
});

it('keeps only the page number in the canonical URL', function () {
    config(['blog.per_page' => 2]);
    paginatedPosts(3);

    $this->get(route('blog.index', ['utm_source' => 'x']))
        ->assertSee('<link rel="canonical" href="'.route('blog.index').'">', false);

    $this->get(route('blog.index', ['page' => 2, 'utm_source' => 'x']))
        ->assertSee('<link rel="canonical" href="'.route('blog.index').'?page=2">', false)
        ->assertSee('<meta property="og:url" content="'.route('blog.index').'?page=2">', false);
});
