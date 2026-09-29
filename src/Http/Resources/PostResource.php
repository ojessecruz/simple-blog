<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Jessecruz\SimpleBlog\Models\Post;

/**
 * @mixin Post
 */
final class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->when(! $request->routeIs('blog.api.posts.index'), $this->body),
            'category' => [
                'slug' => $this->category->slug,
                'name' => $this->category->name,
            ],
            'status' => $this->status(),
            'published_at' => $this->published_at?->toIso8601String(),
            'reading_time' => $this->reading_time,
            'cover_image' => $this->cover_image,
            'og_image' => $this->og_image,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'keywords' => $this->keywords ?? [],
            'author_id' => $this->author_id,
            'url' => $this->url(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    private function status(): string
    {
        return match (true) {
            $this->published_at === null => 'draft',
            $this->isPublished() => 'published',
            default => 'scheduled',
        };
    }
}
