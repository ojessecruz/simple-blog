<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Support;

use Jessecruz\SimpleBlog\Models\Post;

/**
 * Resolves the share buttons rendered on public posts from
 * `config('blog.share')`: which networks are enabled, their share-intent
 * URLs and their accessible labels.
 */
final class Share
{
    /**
     * Every supported network. `copy` (copy link) and `native` (Web Share
     * API) are buttons handled client-side; the rest are plain links.
     */
    public const NETWORKS = ['whatsapp', 'x', 'linkedin', 'facebook', 'telegram', 'email', 'copy', 'native'];

    private const NAMES = [
        'whatsapp' => 'WhatsApp',
        'x' => 'X',
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'telegram' => 'Telegram',
    ];

    /**
     * Enabled networks in the configured order, ignoring unknown entries and
     * duplicates.
     *
     * @return list<string>
     */
    public static function networks(): array
    {
        $configured = config('blog.share.networks', []);

        if (! is_array($configured)) {
            return [];
        }

        $networks = array_filter(
            $configured,
            fn (mixed $network): bool => is_string($network) && in_array($network, self::NETWORKS, true),
        );

        return array_values(array_unique($networks));
    }

    /**
     * Share buttons for a post, ready for the view.
     *
     * @return list<array{network: string, label: string, href: string|null}>
     */
    public static function links(Post $post): array
    {
        return array_map(fn (string $network): array => [
            'network' => $network,
            'label' => self::label($network),
            'href' => self::url($network, $post),
        ], self::networks());
    }

    /**
     * Share-intent URL for a link network, or null for the client-side
     * buttons (`copy`, `native`) and unknown networks.
     */
    public static function url(string $network, Post $post): ?string
    {
        $url = $post->url();
        $title = $post->title;

        return match ($network) {
            'whatsapp' => 'https://wa.me/?'.self::query(['text' => $title.' - '.$url]),
            'x' => 'https://twitter.com/intent/tweet?'.self::query(['url' => $url, 'text' => $title, 'via' => self::xVia()]),
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?'.self::query(['url' => $url]),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?'.self::query(['u' => $url]),
            'telegram' => 'https://t.me/share/url?'.self::query(['url' => $url, 'text' => $title]),
            'email' => 'mailto:?'.self::query(['subject' => $title, 'body' => self::emailBody($post)]),
            default => null,
        };
    }

    public static function label(string $network): string
    {
        return match ($network) {
            'email' => __('blog::messages.share_email'),
            'copy' => __('blog::messages.copy_link'),
            'native' => __('blog::messages.share_native'),
            default => __('blog::messages.share_on', ['network' => self::NAMES[$network] ?? $network]),
        };
    }

    /**
     * X handle credited as "via @handle", without the leading "@".
     */
    public static function xVia(): ?string
    {
        $configured = config('blog.share.x_via');

        if (! is_string($configured)) {
            return null;
        }

        $handle = ltrim(trim($configured), '@');

        return $handle !== '' ? $handle : null;
    }

    private static function emailBody(Post $post): string
    {
        return trim($post->excerpt."\n\n".$post->url());
    }

    /**
     * RFC 3986 encoding (spaces as %20, never "+"), which mail clients
     * require for mailto: and every share endpoint accepts. Null values are
     * dropped.
     *
     * @param  array<string, string|null>  $params
     */
    private static function query(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
