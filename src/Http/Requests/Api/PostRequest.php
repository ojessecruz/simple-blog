<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Requests\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

/**
 * Validates post creation (POST, fields required) and partial updates (PATCH, fields optional).
 */
final class PostRequest extends FormRequest
{
    private const int WORDS_PER_MINUTE = 200;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Exists|Unique|string>>
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        /** @var Post|null $post */
        $post = $this->route('post');

        return [
            'title' => [$presence, 'string', 'max:255'],
            'slug' => [$presence, 'string', 'max:255', 'alpha_dash', Rule::unique('blog_posts', 'slug')->ignore($post?->id)],
            'excerpt' => [$presence, 'string', 'max:255'],
            'body' => [$presence, 'string'],
            'category' => [$presence, 'string', Rule::exists('blog_categories', 'slug')],
            'published_at' => ['nullable', 'date'],
            'reading_time' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'cover_image' => ['nullable', 'url', 'max:2048'],
            'og_image' => ['nullable', 'url', 'max:2048'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:100'],
            'author_id' => $this->authorRules(),
        ];
    }

    /**
     * Maps the validated payload to Post attributes: resolves the category slug and
     * estimates the reading time from the body when it is not sent.
     *
     * @return array<string, mixed>
     */
    public function postAttributes(): array
    {
        $attributes = $this->validated();

        if (array_key_exists('category', $attributes)) {
            $attributes['blog_category_id'] = PostCategory::where('slug', $attributes['category'])->value('id');
            unset($attributes['category']);
        }

        if (isset($attributes['body']) && ! isset($attributes['reading_time'])) {
            $attributes['reading_time'] = $this->estimateReadingTime($attributes['body']);
        }

        return $attributes;
    }

    protected function prepareForValidation(): void
    {
        if ($this->isCreating() && blank($this->input('slug')) && filled($this->input('title'))) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }

    /**
     * `author_id` must reference the host's author model; without one it is accepted as-is,
     * matching the relation that resolves to no author.
     *
     * @return array<int, Exists|string>
     */
    private function authorRules(): array
    {
        /** @var class-string<Model>|null $model */
        $model = config('blog.author_model');

        $rules = ['nullable', 'integer'];

        if ($model !== null) {
            $rules[] = Rule::exists($model, (new $model)->getKeyName());
        }

        return $rules;
    }

    private function isCreating(): bool
    {
        return $this->isMethod('POST');
    }

    private function estimateReadingTime(string $body): int
    {
        $words = count(preg_split('/\s+/u', strip_tags($body), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return max(1, min(120, (int) ceil($words / self::WORDS_PER_MINUTE)));
    }
}
