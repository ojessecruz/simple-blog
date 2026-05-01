<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Livewire\Admin\PostForm;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Livewire\Livewire;

beforeEach(function () {
    $this->category = PostCategory::create(['slug' => 'gestao', 'name' => 'Gestão']);
});

it('auto-generates the slug from the title', function () {
    Livewire::test(PostForm::class)
        ->set('title', 'Como Reduzir Faltas')
        ->assertSet('slug', 'como-reduzir-faltas');
});

it('stops auto-generating the slug once it is manually edited', function () {
    Livewire::test(PostForm::class)
        ->set('title', 'Primeiro')
        ->set('slug', 'meu-slug-custom')
        ->set('title', 'Segundo')
        ->assertSet('slug', 'meu-slug-custom');
});

it('creates a new post with valid data', function () {
    Livewire::test(PostForm::class)
        ->set('title', 'Novo Post')
        ->set('excerpt', 'Resumo curto')
        ->set('body', '## Conteúdo')
        ->set('blog_category_id', $this->category->id)
        ->set('reading_time', 4)
        ->set('keywords', 'agendamento, gestão')
        ->call('save')
        ->assertRedirect(route('blog.admin.index'));

    $post = Post::where('slug', 'novo-post')->first();
    expect($post)->not->toBeNull()
        ->and($post->title)->toBe('Novo Post')
        ->and($post->keywords)->toBe(['agendamento', 'gestão']);
});

it('rejects invalid data', function () {
    Livewire::test(PostForm::class)
        ->call('save')
        ->assertHasErrors(['title', 'slug', 'excerpt', 'body', 'blog_category_id']);
});

it('enforces unique slug on creation', function () {
    Post::create([
        'slug' => 'existente',
        'title' => 'Existente',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $this->category->id,
    ]);

    Livewire::test(PostForm::class)
        ->set('title', 'Existente')
        ->set('slug', 'existente')
        ->set('excerpt', 'Resumo')
        ->set('body', 'Conteúdo')
        ->set('blog_category_id', $this->category->id)
        ->call('save')
        ->assertHasErrors('slug');
});

it('loads an existing post into the form on edit', function () {
    $post = Post::create([
        'slug' => 'post-original',
        'title' => 'Post Original',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $this->category->id,
        'keywords' => ['a', 'b'],
    ]);

    Livewire::test(PostForm::class, ['post' => $post])
        ->assertSet('title', 'Post Original')
        ->assertSet('slug', 'post-original')
        ->assertSet('keywords', 'a, b');
});

it('updates an existing post', function () {
    $post = Post::create([
        'slug' => 'antigo',
        'title' => 'Antigo',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $this->category->id,
    ]);

    Livewire::test(PostForm::class, ['post' => $post])
        ->set('title', 'Novo Título')
        ->call('save')
        ->assertRedirect(route('blog.admin.index'));

    expect($post->fresh()->title)->toBe('Novo Título');
});

it('marks isDraft true when published_at is cleared and false when set', function () {
    Livewire::test(PostForm::class)
        ->assertSet('isDraft', true)
        ->set('published_at', '2026-06-01T10:00')
        ->assertSet('isDraft', false)
        ->set('published_at', '')
        ->assertSet('isDraft', true);
});

it('clears published_at when isDraft is checked and sets it when unchecked', function () {
    $component = Livewire::test(PostForm::class)
        ->set('published_at', '2026-06-01T10:00')
        ->set('isDraft', true)
        ->assertSet('published_at', null);

    $component->set('isDraft', false);
    expect($component->get('published_at'))->not->toBeNull()->not->toBe('');
});

it('clears published_at back to null when the date input is emptied', function () {
    $post = Post::create([
        'slug' => 'p',
        'title' => 'P',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $this->category->id,
        'published_at' => now()->subDay(),
    ]);

    Livewire::test(PostForm::class, ['post' => $post])
        ->set('published_at', '')
        ->call('save')
        ->assertRedirect(route('blog.admin.index'));

    expect($post->fresh()->published_at)->toBeNull();
});

it('saves the post and dispatches an event with the preview url when previewing', function () {
    Livewire::test(PostForm::class)
        ->set('title', 'Preview Post')
        ->set('excerpt', 'Resumo')
        ->set('body', '## Olá')
        ->set('blog_category_id', $this->category->id)
        ->call('preview')
        ->assertDispatched('open-preview');

    expect(Post::where('slug', 'preview-post')->exists())->toBeTrue();
});
