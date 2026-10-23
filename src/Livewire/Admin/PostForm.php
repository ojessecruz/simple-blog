<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\Dates;
use Jessecruz\SimpleBlog\Support\Images;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class PostForm extends Component
{
    use WithFileUploads;

    /** Image fields and the upload property that can replace each one on save. */
    private const UPLOADS = ['cover_image' => 'coverUpload', 'og_image' => 'ogUpload'];

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

    public ?string $cover_image = null;

    /** @var TemporaryUploadedFile|null */
    public $coverUpload = null;

    #[Validate(['nullable', 'date'])]
    public ?string $published_at = null;

    #[Validate(['required', 'integer', 'min:1', 'max:120'])]
    public int $reading_time = 5;

    #[Validate(['nullable', 'string', 'max:255'])]
    public ?string $meta_title = null;

    #[Validate(['nullable', 'string', 'max:500'])]
    public ?string $meta_description = null;

    public ?string $og_image = null;

    /** @var TemporaryUploadedFile|null */
    public $ogUpload = null;

    /** @var TemporaryUploadedFile|null */
    public $bodyUpload = null;

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

    public function updatedCoverUpload(): void
    {
        $this->checkUpload('coverUpload');
    }

    public function updatedOgUpload(): void
    {
        $this->checkUpload('ogUpload');
    }

    /**
     * Stores an image picked in the body editor right away and has the
     * browser insert its Markdown at the cursor.
     */
    public function updatedBodyUpload(): void
    {
        $this->checkUpload('bodyUpload');

        $upload = $this->bodyUpload;
        $this->reset('bodyUpload');

        if (! Images::enabled() || ! $upload instanceof TemporaryUploadedFile) {
            return;
        }

        $path = Images::store($upload, $this->slug !== '' ? $this->slug : $this->title);

        if ($path === null) {
            $this->addError('bodyUpload', __('blog::messages.image_store_failed'));

            return;
        }

        // The file name, minus what would end the Markdown alt text, as a first alt to edit.
        $name = str_replace(['[', ']', '\\'], '', pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME));
        $alt = trim((string) preg_replace('/[-_\s]+/', ' ', $name));

        $this->dispatch('blog-insert-image', markdown: '!['.$alt.']('.Images::url($path).')');
    }

    /** Empties an image field and drops its pending upload. */
    public function clearImage(string $field): void
    {
        if (isset(self::UPLOADS[$field])) {
            $this->{$field} = null;
            $this->reset(self::UPLOADS[$field]);
        }
    }

    /** What the form shows for an image field: the pending upload, else its URL. */
    public function imagePreview(string $field): ?string
    {
        if (! isset(self::UPLOADS[$field])) {
            return null;
        }

        $upload = $this->{self::UPLOADS[$field]};

        if ($upload instanceof TemporaryUploadedFile) {
            return $upload->isPreviewable() ? $upload->temporaryUrl() : null;
        }

        $url = $this->{$field};

        return is_string($url) && (Str::isUrl($url, ['http', 'https']) || str_starts_with($url, '/')) ? $url : null;
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
        $maxUpload = Images::maxSize();

        return view('blog::livewire.admin.post-form', [
            'categories' => PostCategory::orderBy('name')->get(),
            'imagesEnabled' => Images::enabled(),
            'maxUpload' => $maxUpload >= 1024 ? round($maxUpload / 1024, 1).' MB' : $maxUpload.' KB',
        ])->layout(config('blog.layouts.admin'));
    }

    protected function rules(): array
    {
        return [
            'cover_image' => Images::urlRules(),
            'og_image' => Images::urlRules(),
        ];
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
            'cover_image' => __('blog::messages.cover_image'),
            'coverUpload' => __('blog::messages.cover_image'),
            'published_at' => __('blog::messages.publish_at'),
            'reading_time' => __('blog::messages.reading_time'),
            'meta_title' => __('blog::messages.meta_title'),
            'meta_description' => __('blog::messages.meta_description'),
            'og_image' => __('blog::messages.og_image'),
            'ogUpload' => __('blog::messages.og_image'),
            'bodyUpload' => __('blog::messages.body_image'),
        ];
    }

    private function persist(): void
    {
        $data = $this->validate();
        $this->validate(
            ['slug' => [Rule::unique('blog_posts', 'slug')->ignore($this->post?->id), Rule::notIn(Post::RESERVED_SLUGS)]],
            ['slug.not_in' => __('blog::messages.slug_reserved')],
        );

        if (Images::enabled()) {
            $this->validate(array_fill_keys(self::UPLOADS, Images::uploadRules()));

            foreach (self::UPLOADS as $field => $upload) {
                if ($this->{$upload} instanceof TemporaryUploadedFile) {
                    $data[$field] = Images::store($this->{$upload}, $data['slug'])
                        ?? throw ValidationException::withMessages([$upload => __('blog::messages.image_store_failed')]);
                }
            }
        }

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

        // Stored uploads now show as their URL; a second save mustn't store them again.
        $this->cover_image = $this->post->cover_image;
        $this->og_image = $this->post->og_image;
        $this->reset(self::UPLOADS);
    }

    /** Validates a picked image right away, dropping it when it's refused. */
    private function checkUpload(string $property): void
    {
        try {
            $this->validateOnly($property, [$property => Images::uploadRules()]);
        } catch (ValidationException $e) {
            $this->reset($property);

            throw $e;
        }
    }
}
