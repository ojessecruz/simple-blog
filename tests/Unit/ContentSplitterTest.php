<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Jessecruz\SimpleBlog\Support\ContentSplitter;

function splitterHtml(string $markdown): string
{
    return Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
}

/**
 * DOM serialization rewrites a few equivalent spellings (`<br />` → `<br>`,
 * `&quot;` → `"`), so compare with those and all whitespace normalized away.
 */
function normalizeSplitterHtml(string $html): string
{
    $html = preg_replace('#\s*/>#', '>', str_replace('&quot;', '"', $html));

    return preg_replace('/\s+/u', '', $html);
}

it('splits right before the second h2 when the post has two or more', function () {
    $html = splitterHtml("Intro.\n\n## Primeira\n\nTexto um.\n\n## Segunda\n\nTexto dois.\n\n## Terceira\n\nTexto três.");

    [$before, $after] = ContentSplitter::splitForMidCta($html);

    expect($before)->toContain('<h2>Primeira</h2>')->toEndWith('<p>Texto um.</p>')
        ->and($after)->toStartWith('<h2>Segunda</h2>')->toContain('<h2>Terceira</h2>');
});

it('falls back to the block ending nearest 40% of the content when there is no h2', function () {
    $paragraphs = array_map(fn (int $i) => "Parágrafo {$i} ".str_repeat('x', 90), range(1, 10));
    $html = splitterHtml(implode("\n\n", $paragraphs));

    [$before, $after] = ContentSplitter::splitForMidCta($html);

    expect(substr_count($before, '<p>'))->toBe(4)
        ->and(substr_count($after, '<p>'))->toBe(6)
        ->and($before)->toEndWith('</p>')->toContain('Parágrafo 4 ')
        ->and($after)->toStartWith('<p>Parágrafo 5 ');
});

it('uses the 40% fallback when there is a single h2', function () {
    $html = splitterHtml("## Única\n\n".implode("\n\n", array_fill(0, 5, str_repeat('texto ', 20))));

    [$before, $after] = ContentSplitter::splitForMidCta($html);

    expect($before)->toStartWith('<h2>Única</h2>')
        ->and(substr_count($before, '<p>'))->toBe(2)
        ->and(substr_count($after, '<p>'))->toBe(3);
});

it('returns null for short or empty content', function (string $markdown) {
    expect(ContentSplitter::splitForMidCta(splitterHtml($markdown)))->toBeNull();
})->with([
    'empty' => [''],
    'one paragraph' => ['Só um parágrafo.'],
    'three blocks' => ["## A\n\nTexto.\n\n## B"],
]);

it('never places the cta before the first or after the last block', function () {
    $html = splitterHtml("Um.\n\nDois.\n\nTrês.\n\n".str_repeat('longo ', 400));

    [$before, $after] = ContentSplitter::splitForMidCta($html);

    expect($before)->not->toBe('')
        ->and($after)->toStartWith('<p>longo')
        ->and($before)->toContain('Três.');
});

it('never splits inside lists, blockquotes, tables or code blocks', function () {
    $items = implode("\n", array_map(fn (int $i) => "- item {$i} ".str_repeat('y', 40), range(1, 20)));
    $quote = implode("\n>\n", array_map(fn (int $i) => "> citação {$i} ".str_repeat('z', 40), range(1, 10)));
    $html = splitterHtml("Abertura.\n\n{$items}\n\n{$quote}\n\n| a | b |\n|---|---|\n| 1 | 2 |\n\n```\n<p>código</p>\n```\n\nFim.");

    [$before, $after] = ContentSplitter::splitForMidCta($html);

    foreach (['ul', 'blockquote', 'table', 'pre'] as $tag) {
        expect(substr_count($before, "<{$tag}>"))->toBe(substr_count($before, "</{$tag}>"))
            ->and(substr_count($after, "<{$tag}>"))->toBe(substr_count($after, "</{$tag}>"));
    }

    expect($before)->toContain('<ul>')->toContain('</ul>');
});

it('preserves non-ASCII characters', function () {
    $html = splitterHtml("Introdução à gestão.\n\n## Ação\n\nAtenção: “aspas” — ü, ñ, 😀.\n\n## Conclusão\n\nÉ isso.");

    [$before, $after] = ContentSplitter::splitForMidCta($html);

    expect($before)->toContain('Introdução à gestão.')->toContain('<h2>Ação</h2>')->toContain('Atenção: “aspas” — ü, ñ, 😀.')
        ->and($after)->toContain('<h2>Conclusão</h2>')->toContain('É isso.')
        ->and($before.$after)->not->toContain('&');
});

it('keeps all content when concatenating the two halves', function () {
    $markdown = "Intro com **negrito** e [link](https://example.com).\n\n## Primeira\n\nTexto \"citado\" com `<code>`  \nquebra.\n\n- a\n- b\n\n---\n\n## Segunda\n\n> citação\n\n1. um\n2. dois\n\n![img](https://example.com/a.png)";
    $html = splitterHtml($markdown);

    [$before, $after] = ContentSplitter::splitForMidCta($html);

    expect(normalizeSplitterHtml($before.$after))->toBe(normalizeSplitterHtml($html));
});

it('returns null when raw html escapes the wrapper', function () {
    expect(ContentSplitter::splitForMidCta('<p>a</p></div><p>b</p><p>c</p><p>d</p>'))->toBeNull();
});
