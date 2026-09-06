<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Support;

use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

/**
 * Resolves the SEO values (titles, descriptions, images, JSON-LD) used by the
 * public views from `config('blog.seo')`, with sensible fallbacks so a host
 * app gets correct markup without configuring anything.
 */
final class Seo
{
    public static function siteName(): string
    {
        $configured = config('blog.seo.site_name');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $appName = config('app.name');

        return is_string($appName) && $appName !== '' ? $appName : 'Blog';
    }

    /**
     * The blog's own name. Used as the index title and as the suffix of every
     * other public page title.
     */
    public static function blogName(): string
    {
        $configured = config('blog.seo.blog_name');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return 'Blog | '.self::siteName();
    }

    public static function indexTitle(): string
    {
        $configured = config('blog.seo.index_title');

        return is_string($configured) && $configured !== '' ? $configured : self::blogName();
    }

    public static function categoryTitle(PostCategory $category): string
    {
        return $category->name.' | '.self::blogName();
    }

    public static function postTitle(Post $post): string
    {
        $title = is_string($post->meta_title) && $post->meta_title !== '' ? $post->meta_title : $post->title;

        return $title.' | '.self::blogName();
    }

    public static function indexDescription(?PostCategory $category = null): ?string
    {
        if ($category !== null && is_string($category->description) && $category->description !== '') {
            return $category->description;
        }

        $configured = config('blog.seo.description');

        return is_string($configured) && $configured !== '' ? $configured : null;
    }

    public static function postDescription(Post $post): ?string
    {
        if (is_string($post->meta_description) && $post->meta_description !== '') {
            return $post->meta_description;
        }

        return $post->excerpt !== '' ? $post->excerpt : null;
    }

    /**
     * Default social image for listing pages, as an absolute URL.
     */
    public static function defaultImage(): ?string
    {
        $configured = config('blog.seo.image');

        return is_string($configured) && $configured !== '' ? $configured : null;
    }

    /**
     * Social image for a post: explicit og_image, then cover image, then the
     * configured default.
     */
    public static function postImage(Post $post): ?string
    {
        foreach ([$post->og_image, $post->cover_image] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return self::defaultImage();
    }

    public static function language(): string
    {
        return str_replace('_', '-', app()->getLocale());
    }

    /**
     * Schema.org Article for a published post, ready for json_encode().
     *
     * @return array<string, mixed>
     */
    public static function articleSchema(Post $post): array
    {
        $publishedAt = $post->published_at;
        $modifiedAt = $post->updated_at ?? $publishedAt;

        $publisher = [
            '@type' => 'Organization',
            'name' => self::siteName(),
            'url' => url('/'),
        ];

        $logo = config('blog.seo.publisher_logo');

        if (is_string($logo) && $logo !== '') {
            $publisher['logo'] = ['@type' => 'ImageObject', 'url' => $logo];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'inLanguage' => self::language(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $post->url()],
            'datePublished' => $publishedAt?->toIso8601String(),
            'dateModified' => $modifiedAt?->toIso8601String(),
            'author' => ['@type' => 'Person', 'name' => $post->authorName()],
            'publisher' => $publisher,
        ];

        $image = self::postImage($post);

        if ($image !== null) {
            $schema['image'] = [$image];
        }

        return array_filter($schema, fn (mixed $value): bool => $value !== null);
    }
}
