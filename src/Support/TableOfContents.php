<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Support;

use Jessecruz\SimpleBlog\Models\Post;

/**
 * Renders a post body with linkable headings and builds its table of
 * contents from `config('blog.toc')`.
 *
 * @phpstan-type TocItem array{level: int, id: string, text: string, children: array<int, mixed>}
 */
final class TableOfContents
{
    /**
     * The post's body HTML — an id on every heading, plus a "#" permalink
     * when `toc.permalinks` is on — and the table of contents for it, from
     * the same render so the links always match the ids.
     *
     * @return array{0: string, 1: list<TocItem>}
     */
    public static function render(Post $post): array
    {
        $anchors = new HeadingAnchors((bool) config('blog.toc.permalinks', true));
        $html = $post->renderedBody([$anchors]);

        return [$html, self::items($anchors->headings())];
    }

    /**
     * Nested table of contents for a post's headings, or an empty list when
     * it's disabled or the post has fewer than `toc.min_headings` listed
     * headings. Levels are counted from the post's top heading level, so a
     * body that starts at `###` (or uses `#`) still gets one.
     *
     * @param  list<array{level: int, id: string, text: string}>  $headings
     * @return list<TocItem>
     */
    public static function items(array $headings): array
    {
        if (! config('blog.toc.enabled', true) || $headings === []) {
            return [];
        }

        $top = min(array_column($headings, 'level'));
        $depth = max(1, (int) config('blog.toc.depth', 2));
        $listed = array_values(array_filter($headings, fn (array $heading): bool => $heading['level'] < $top + $depth));

        if (count($listed) < max(1, (int) config('blog.toc.min_headings', 3))) {
            return [];
        }

        return self::nest($listed);
    }

    /**
     * Groups each heading under the closest preceding one of a higher level.
     *
     * @param  list<array{level: int, id: string, text: string}>  $headings
     * @return list<TocItem>
     */
    private static function nest(array $headings): array
    {
        $items = [];

        foreach ($headings as $heading) {
            $last = array_key_last($items);

            if ($last !== null && $heading['level'] > $items[$last]['level']) {
                $items[$last]['children'][] = $heading;
            } else {
                $items[] = [...$heading, 'children' => []];
            }
        }

        return array_map(fn (array $item): array => [...$item, 'children' => self::nest($item['children'])], $items);
    }
}
