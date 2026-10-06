<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Splits a post's rendered HTML at a top-level block boundary so the public
 * view can insert the optional `mid_cta_view` inside the body. Splits never
 * happen inside a list, table, blockquote, pre, etc. — only between the
 * direct children of the CommonMark output.
 */
final class ContentSplitter
{
    /** Posts with fewer top-level blocks than this get no mid-article CTA. */
    public const MIN_BLOCKS = 4;

    /** Fallback insertion target, as a fraction of the post's text length. */
    public const FALLBACK_RATIO = 0.4;

    /**
     * Returns [before, after] HTML, or null when there's no good insertion
     * point. Placement: before the second top-level <h2> when there are two
     * or more, otherwise after the block that ends nearest ~40% of the text.
     * The split is never before the first block nor after the last one.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function splitForMidCta(string $html): ?array
    {
        if (trim($html) === '') {
            return null;
        }

        $nodes = self::topLevelNodes($html);

        if ($nodes === null) {
            return null;
        }

        // Positions (in $nodes) of the meaningful blocks; blank text between them is kept but not counted.
        $blocks = [];
        foreach ($nodes as $position => $node) {
            if (! ($node->nodeType === XML_TEXT_NODE && trim((string) $node->textContent) === '')) {
                $blocks[] = $position;
            }
        }

        if (count($blocks) < self::MIN_BLOCKS) {
            return null;
        }

        $splitBlock = self::splitBlockIndex($nodes, $blocks);
        $splitAt = $blocks[$splitBlock];

        $before = '';
        $after = '';
        foreach ($nodes as $position => $node) {
            $serialized = (string) $node->ownerDocument?->saveHTML($node);

            if ($position < $splitAt) {
                $before .= $serialized;
            } else {
                $after .= $serialized;
            }
        }

        return [trim($before), trim($after)];
    }

    /**
     * Index (into $blocks) of the block the CTA goes in front of. Always in
     * [1, count($blocks) - 1], so there's content on both sides.
     *
     * @param  list<DOMNode>  $nodes
     * @param  list<int>  $blocks
     */
    private static function splitBlockIndex(array $nodes, array $blocks): int
    {
        $h2s = [];
        foreach ($blocks as $index => $position) {
            $node = $nodes[$position];
            if ($node instanceof DOMElement && strtolower($node->tagName) === 'h2') {
                $h2s[] = $index;
            }
        }

        if (count($h2s) >= 2) {
            return $h2s[1];
        }

        $weights = array_map(
            fn (int $position): int => mb_strlen(trim((string) $nodes[$position]->textContent)),
            $blocks,
        );

        // Text-less content (e.g. only images) falls back to counting blocks.
        if (array_sum($weights) === 0) {
            $weights = array_fill(0, count($blocks), 1);
        }

        $target = array_sum($weights) * self::FALLBACK_RATIO;
        $cumulative = 0;
        $bestBlock = 0;
        $bestDistance = INF;

        // The CTA goes after block $i; the last block is excluded so it never lands at the very end.
        for ($i = 0; $i < count($blocks) - 1; $i++) {
            $cumulative += $weights[$i];
            $distance = abs($cumulative - $target);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestBlock = $i;
            }
        }

        return $bestBlock + 1;
    }

    /**
     * Parses the HTML inside a wrapper element and returns the wrapper's
     * children, or null when the markup escapes the wrapper (e.g. a stray
     * closing tag in raw HTML) and splitting could drop content.
     *
     * @return list<DOMNode>|null
     */
    private static function topLevelNodes(string $html): ?array
    {
        $document = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><div data-blog-splitter-root>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $root = null;
        foreach ($document->childNodes as $child) {
            if ($child->nodeType === XML_PI_NODE) {
                continue;
            }

            if ($root !== null || ! ($child instanceof DOMElement) || ! $child->hasAttribute('data-blog-splitter-root')) {
                return null;
            }

            $root = $child;
        }

        if ($root === null) {
            return null;
        }

        $nodes = [];
        foreach ($root->childNodes as $child) {
            $nodes[] = $child;
        }

        return $nodes;
    }
}
