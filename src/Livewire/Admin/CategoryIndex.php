<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class CategoryIndex extends Component
{
    public ?int $editingId = null;

    #[Validate(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Validate(['required', 'string', 'max:255', 'alpha_dash'])]
    public string $slug = '';

    #[Validate(['nullable', 'string', 'max:500'])]
    public string $description = '';

    public bool $autoSlug = true;

    public function updatedName(string $value): void
    {
        if ($this->autoSlug) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(): void
    {
        $this->autoSlug = false;
    }

    public function edit(int $id): void
    {
        $category = PostCategory::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->autoSlug = false;
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'description']);
        $this->autoSlug = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $this->validate(['slug' => Rule::unique('blog_categories', 'slug')->ignore($this->editingId)]);

        if ($this->editingId) {
            PostCategory::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', 'Categoria atualizada.');
        } else {
            PostCategory::create($data);
            $this->dispatch('notify', 'Categoria criada.');
        }

        $this->cancel();
    }

    public function delete(int $id): void
    {
        $category = PostCategory::findOrFail($id);

        if ($category->posts()->exists()) {
            $this->dispatch('notify', 'Não é possível excluir: a categoria possui posts.');

            return;
        }

        $category->delete();
        $this->dispatch('notify', 'Categoria excluída.');
    }

    public function render(): View
    {
        return view('blog::livewire.admin.category-index', [
            'categories' => PostCategory::withCount('posts')->orderBy('name')->get(),
        ])->extends(config('blog.layouts.admin'))->section('content');
    }
}
