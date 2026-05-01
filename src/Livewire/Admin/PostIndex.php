<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Jessecruz\SimpleBlog\Models\Post;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class PostIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function delete(int $postId): void
    {
        Post::findOrFail($postId)->delete();
        $this->dispatch('notify', 'Post excluído.');
    }

    public function render(): View
    {
        $query = Post::query()->with('category');

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter === 'published') {
            $query->whereNotNull('published_at')->where('published_at', '<=', now());
        } elseif ($this->statusFilter === 'draft') {
            $query->whereNull('published_at');
        } elseif ($this->statusFilter === 'scheduled') {
            $query->whereNotNull('published_at')->where('published_at', '>', now());
        }

        return view('blog::livewire.admin.post-index', [
            'posts' => $query->latest('published_at')->latest('id')->paginate(15),
        ])->extends(config('blog.layouts.admin'))->section('content');
    }
}
