<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\Dates;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class PostForm extends Component
{
    public ?Post $post = null;

    #[Validate(['required', 'string', 'max:255'])]
    public string $title = '';

    #[Validate(['required', 'string', 'max:255', 'alpha_dash'])]
    public string $slug = '';

    #[Validate(['required', 'string', 'max:500'])]
    public string $excerpt = '';

    #[Validate(['required', 'string'])]
    public string $body = '';

    #[Validate(['required', 'exists:blog_categories,id'])]
    public ?int $blog_category_id = null;

    #[Validate(['nullable', 'url', 'max:2048'])]
    public ?string $cover_image = null;

    #[Validate(['nullable', 'date'])]
    public ?string $published_at = null;

    #[Validate(['required', 'integer', 'min:1', 'max:120'])]
    public int $reading_time = 5;

    #[Validate(['nullable', 'string', 'max:255'])]
    public ?string $meta_title = null;

    #[Validate(['nullable', 'string', 'max:500'])]
    public ?string $meta_description = null;

    #[Validate(['nullable', 'url', 'max:2048'])]
    public ?string $og_image = null;

    public string $keywords = '';

    public bool $autoSlug = true;

    public bool $autoReadingTime = true;

    public bool $isDraft = true;

    public bool $splitView = false;

    public function mount(?Post $post = null): void
    {
        if ($post?->exists) {
            $this->post = $post;
            $this->title = $post->title;
            $this->slug = $post->slug;
            $this->excerpt = $post->excerpt;
            $this->body = $post->body;
            $this->blog_category_id = $post->blog_category_id;
            $this->cover_image = $post->cover_image;
            $this->published_at = Dates::toInput($post->published_at);
            $this->reading_time = $post->reading_time;
            $this->meta_title = $post->meta_title;
            $this->meta_description = $post->meta_description;
            $this->og_image = $post->og_image;
            $this->keywords = implode(', ', $post->keywords ?? []);
            $this->autoSlug = false;
            $this->autoReadingTime = false;
            $this->isDraft = $post->published_at === null;
        }
    }

    public function updatedTitle(string $value): void
    {
        if ($this->autoSlug) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(): void
    {
        $this->autoSlug = false;
    }

    public function updatedBody(string $value): void
    {
        if ($this->autoReadingTime) {
            $this->reading_time = Post::estimateReadingTime($value);
        }
    }

    public function updatedReadingTime(): void
    {
        $this->autoReadingTime = false;
    }

    public function updatedPublishedAt(?string $value): void
    {
        $this->isDraft = $value === null || $value === '';
    }

    public function updatedIsDraft(bool $value): void
    {
        if ($value) {
            $this->published_at = null;
        } elseif ($this->published_at === null || $this->published_at === '') {
            $this->published_at = Dates::toInput(now());
        }
    }

    public function save(): void
    {
        $this->persist();
        $this->dispatch('notify', $this->post?->wasRecentlyCreated ? __('blog::messages.post_created') : __('blog::messages.post_updated'));
        $this->redirectRoute('blog.admin.index', navigate: true);
    }

    public function preview(): void
    {
        $this->persist();
        $this->dispatch('open-preview', url: route('blog.admin.preview', $this->post));
    }

    public function render(): View
    {
        return view('blog::livewire.admin.post-form', [
            'categories' => PostCategory::orderBy('name')->get(),
        ])->layout(config('blog.layouts.admin'));
    }

    /**
     * Field names in validation messages, labelled as in the form. The messages
     * themselves come from the host app's validation translations.
     */
    protected function validationAttributes(): array
    {
        return [
            'title' => __('blog::messages.title'),
            'slug' => __('blog::messages.slug'),
            'excerpt' => __('blog::messages.excerpt'),
            'body' => __('blog::messages.content_markdown'),
            'blog_category_id' => __('blog::messages.category'),
            'cover_image' => __('blog::messages.cover_image_url'),
            'published_at' => __('blog::messages.publish_at'),
            'reading_time' => __('blog::messages.reading_time'),
            'meta_title' => __('blog::messages.meta_title'),
            'meta_description' => __('blog::messages.meta_description'),
            'og_image' => __('blog::messages.og_image_url'),
        ];
    }

    private function persist(): void
    {
        $data = $this->validate();
        $this->validate(
            ['slug' => [Rule::unique('blog_posts', 'slug')->ignore($this->post?->id), Rule::notIn(Post::RESERVED_SLUGS)]],
            ['slug.not_in' => __('blog::messages.slug_reserved')],
        );

        foreach (['published_at', 'cover_image', 'og_image', 'meta_title', 'meta_description'] as $nullable) {
            if (($data[$nullable] ?? null) === '') {
                $data[$nullable] = null;
            }
        }

        // The input holds a wall-clock time in the blog timezone.
        if ($data['published_at'] !== null) {
            $data['published_at'] = Dates::fromInput($data['published_at']);
        }

        $data['keywords'] = $this->keywords === ''
            ? []
            : array_values(array_filter(array_map('trim', explode(',', $this->keywords))));
        $data['author_id'] = auth()->id();

        if ($this->post) {
            $this->post->update($data);
        } else {
            $this->post = Post::create($data);
        }
    }
}
