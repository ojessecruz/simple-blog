<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Livewire\Admin\PostIndex;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Livewire\Livewire;

function makeCategory(array $attrs = []): PostCategory
{
    static $i = 0;
    $i++;

    return PostCategory::create(array_merge([
        'slug' => 'cat-'.$i,
        'name' => 'Categoria '.$i,
    ], $attrs));
}

function makePost(array $attrs = []): Post
{
    static $i = 0;
    $i++;

    return Post::create(array_merge([
        'slug' => 'post-'.$i,
        'title' => 'Post '.$i,
        'excerpt' => 'Resumo',
        'body' => 'Conteúdo',
        'blog_category_id' => makeCategory()->id,
        'published_at' => now()->subDay(),
    ], $attrs));
}

it('lists all posts', function () {
    makePost(['title' => 'Visível']);

    Livewire::test(PostIndex::class)->assertSee('Visível');
});

it('filters by status: draft', function () {
    makePost(['title' => 'Pub Live', 'published_at' => now()->subDay()]);
    makePost(['title' => 'Pub Draft', 'published_at' => null]);

    Livewire::test(PostIndex::class)
        ->set('statusFilter', 'draft')
        ->assertSee('Pub Draft')
        ->assertDontSee('Pub Live');
});

it('searches by title', function () {
    makePost(['title' => 'WhatsApp lembretes']);
    makePost(['title' => 'Outro assunto']);

    Livewire::test(PostIndex::class)
        ->set('search', 'WhatsApp')
        ->assertSee('WhatsApp lembretes')
        ->assertDontSee('Outro assunto');
});

it('deletes a post', function () {
    $post = makePost();

    Livewire::test(PostIndex::class)->call('delete', $post->id);

    expect(Post::find($post->id))->toBeNull();
});
