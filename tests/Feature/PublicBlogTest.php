<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Tests\Stubs\User;

it('renders the public index with published posts', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    Post::create([
        'slug' => 'visivel',
        'title' => 'Post Visível',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ]);
    Post::create([
        'slug' => 'rascunho',
        'title' => 'Post Rascunho',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => null,
    ]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Post Visível')
        ->assertDontSee('Post Rascunho');
});

it('renders a published post by slug with markdown converted to html', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create([
        'slug' => 'meu-post',
        'title' => 'Meu Post',
        'excerpt' => 'Resumo curto',
        'body' => "## Cabeçalho\n\nUm parágrafo com **negrito**.",
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('<h2>Cabeçalho</h2>', false)
        ->assertSee('<strong>negrito</strong>', false);
});

it('returns 404 for unpublished posts on the public route', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create([
        'slug' => 'rascunho',
        'title' => 'R',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => null,
    ]);

    $this->get(route('blog.show', $post))->assertNotFound();
});

it('renders the preview route for unpublished posts (no isPublished check)', function () {
    $admin = User::create(['name' => 'Admin']);
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create([
        'slug' => 'rascunho-secreto',
        'title' => 'Rascunho Secreto',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('blog.admin.preview', $post))
        ->assertOk()
        ->assertSee('Rascunho Secreto');
});

it('does not increment views_count when previewing', function () {
    $admin = User::create(['name' => 'Admin']);
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create([
        'slug' => 'p',
        'title' => 'P',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'views_count' => 0,
    ]);

    $this->actingAs($admin)->get(route('blog.admin.preview', $post));

    expect($post->fresh()->views_count)->toBe(0);
});

it('filters posts by category', function () {
    $a = PostCategory::create(['slug' => 'a', 'name' => 'Alpha']);
    $b = PostCategory::create(['slug' => 'b', 'name' => 'Beta']);
    Post::create(['slug' => 'pa', 'title' => 'Post Alpha', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $a->id, 'published_at' => now()->subDay()]);
    Post::create(['slug' => 'pb', 'title' => 'Post Beta', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $b->id, 'published_at' => now()->subDay()]);

    $this->get(route('blog.category', $a))
        ->assertOk()
        ->assertSee('Post Alpha')
        ->assertDontSee('Post Beta');
});
