<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Jessecruz\SimpleBlog\Contracts\Author;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $excerpt
 * @property string $body
 * @property int $blog_category_id
 * @property string|null $cover_image
 * @property int|null $author_id
 * @property Carbon|null $published_at
 * @property int $reading_time
 * @property int $views_count
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image
 * @property array<int, string>|null $keywords
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PostCategory $category
 * @property-read Model|null $author
 */
final class Post extends Model
{
    protected $table = 'blog_posts';

    protected $attributes = [
        'reading_time' => 5,
        'views_count' => 0,
    ];

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'body',
        'blog_category_id',
        'cover_image',
        'author_id',
        'published_at',
        'reading_time',
        'views_count',
        'meta_title',
        'meta_description',
        'og_image',
        'keywords',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        /** @var class-string<Model>|null $model */
        $model = config('blog.author_model');

        // Without a configured model the relation must remain callable (views
        // and eager loads still reference it), so bind it to this model with
        // an always-false constraint — it resolves to null and never matches.
        if ($model === null) {
            return $this->belongsTo(self::class, 'author_id')->whereRaw('1 = 0');
        }

        return $this->belongsTo($model, 'author_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function url(): string
    {
        return route('blog.show', $this);
    }

    public function authorName(): string
    {
        $author = $this->author;

        return $author instanceof Author ? $author->getBlogAuthorName() : 'Team';
    }

    public function authorInitials(): string
    {
        $author = $this->author;

        if ($author instanceof Author) {
            return $author->getBlogAuthorInitials();
        }

        $words = preg_split('/\s+/', trim($this->authorName())) ?: [];
        $initials = array_map(
            fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)),
            array_slice($words, 0, 2),
        );

        return implode('', $initials);
    }

    public function authorAvatarUrl(): ?string
    {
        $author = $this->author;

        return $author instanceof Author ? $author->getBlogAuthorAvatarUrl() : null;
    }

    public function renderedBody(): string
    {
        return Str::markdown($this->body, config('blog.markdown', [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'keywords' => 'array',
            'reading_time' => 'int',
            'views_count' => 'int',
        ];
    }
}
