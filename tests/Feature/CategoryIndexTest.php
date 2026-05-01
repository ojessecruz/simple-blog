<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Livewire\Admin\CategoryIndex;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Livewire\Livewire;

it('lists existing categories', function () {
    PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);

    Livewire::test(CategoryIndex::class)->assertSee('Gestão');
});

it('creates a new category with auto-slug', function () {
    Livewire::test(CategoryIndex::class)
        ->set('name', 'Nova Cat')
        ->assertSet('slug', 'nova-cat')
        ->call('save');

    expect(PostCategory::where('slug', 'nova-cat')->exists())->toBeTrue();
});

it('rejects invalid data on save', function () {
    Livewire::test(CategoryIndex::class)
        ->call('save')
        ->assertHasErrors(['name', 'slug']);
});

it('loads a category for editing and updates it', function () {
    $category = PostCategory::create(['slug' => 'antiga', 'name' => 'Antiga']);

    Livewire::test(CategoryIndex::class)
        ->call('edit', $category->id)
        ->assertSet('name', 'Antiga')
        ->set('name', 'Atualizada')
        ->call('save');

    expect($category->fresh()->name)->toBe('Atualizada');
});

it('deletes an empty category', function () {
    $category = PostCategory::create(['slug' => 'c', 'name' => 'C']);

    Livewire::test(CategoryIndex::class)->call('delete', $category->id);

    expect(PostCategory::find($category->id))->toBeNull();
});

it('refuses to delete a category that has posts', function () {
    $category = PostCategory::create(['slug' => 'c', 'name' => 'C']);
    Post::create([
        'slug' => 'p',
        'title' => 'P',
        'excerpt' => 'r',
        'body' => 'b',
        'blog_category_id' => $category->id,
    ]);

    Livewire::test(CategoryIndex::class)->call('delete', $category->id);

    expect(PostCategory::find($category->id))->not->toBeNull();
});
