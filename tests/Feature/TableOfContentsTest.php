<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\TableOfContents;

function tocPost(string $body, array $attributes = []): Post
{
    $category = PostCategory::firstOrCreate(['slug' => 'g'], ['name' => 'Gestão']);

    return Post::create(array_merge([
        'slug' => 'guia',
        'title' => 'Guia',
        'excerpt' => 'r',
        'body' => $body,
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ], $attributes));
}

/**
 * The table of contents as nested [id => children] pairs.
 */
function tocOutline(array $items): array
{
    return array_map(fn (array $item): array => [$item['id'] => tocOutline($item['children'])], $items);
}

const TOC_BODY = <<<'MD'
Intro paragraph.

## Como começar

Texto.

### Instalação

Texto.

#### Detalhe fino

Texto.

## Perguntas frequentes

Texto.

## Perguntas frequentes

Texto.
MD;

it('gives every heading a unique slug id and a permalink', function () {
    [$html] = TableOfContents::render(tocPost(TOC_BODY));

    expect($html)
        ->toContain('<h2 id="como-comecar">Como começar<a class="blog-heading-anchor" aria-label="Link to the section “Como começar”" href="#como-comecar">#</a></h2>')
        ->toContain('<h3 id="instalacao">')
        ->toContain('<h4 id="detalhe-fino">')
        ->toContain('<h2 id="perguntas-frequentes">')
        ->toContain('<h2 id="perguntas-frequentes-2">')
        ->toContain('href="#perguntas-frequentes-2"');
});

it('builds a nested table of contents from the top two heading levels', function () {
    [, $toc] = TableOfContents::render(tocPost(TOC_BODY));

    expect(tocOutline($toc))->toBe([
        ['como-comecar' => [['instalacao' => []]]],
        ['perguntas-frequentes' => []],
        ['perguntas-frequentes-2' => []],
    ]);
});

it('renders the table of contents above the body of the post page', function () {
    $post = tocPost(TOC_BODY);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('data-blog-toc', false)
        ->assertSee('In this article')
        ->assertSeeInOrder([
            'data-blog-toc',
            'href="#como-comecar"', 'Como começar',
            'href="#instalacao"', 'Instalação',
            'href="#perguntas-frequentes"',
            'href="#perguntas-frequentes-2"',
            'class="blog-content"',
            '<h2 id="como-comecar">',
        ], false)
        // The table lists two levels; the h4 keeps only its own permalink.
        ->assertDontSee('<a href="#detalhe-fino"', false);
});

it('skips the table of contents for posts with few headings', function () {
    $post = tocPost("## Um\n\nTexto.\n\n## Dois\n\nTexto.");

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertDontSee('data-blog-toc', false)
        ->assertSee('<h2 id="um">', false);

    config(['blog.toc.min_headings' => 2]);

    $this->get(route('blog.show', $post))->assertSee('data-blog-toc', false);
});

it('counts levels from the top heading of the post', function () {
    $post = tocPost("# Um\n\n## Um ponto um\n\n### Fundo\n\n# Dois\n\n# Três");

    [, $toc] = TableOfContents::render($post);

    expect(array_column($toc, 'id'))->toBe(['um', 'dois', 'tres'])
        ->and(array_column($toc[0]['children'], 'id'))->toBe(['um-ponto-um'])
        ->and($toc[0]['children'][0]['children'])->toBe([]);

    config(['blog.toc.depth' => 1]);

    expect(TableOfContents::render($post)[1][0]['children'])->toBe([]);
});

it('follows the toc config switches', function () {
    $post = tocPost(TOC_BODY);

    config(['blog.toc.enabled' => false]);

    $this->get(route('blog.show', $post))
        ->assertDontSee('data-blog-toc', false)
        ->assertSee('<h2 id="como-comecar">Como começar<a class="blog-heading-anchor"', false);

    config(['blog.toc.enabled' => true, 'blog.toc.permalinks' => false]);

    $this->get(route('blog.show', $post))
        ->assertSee('data-blog-toc', false)
        ->assertSee('<h2 id="como-comecar">Como começar</h2>', false)
        ->assertDontSee('<a class="blog-heading-anchor"', false);
});

it('keeps heading text escaped in the headings and the table of contents', function () {
    $post = tocPost("## Use <script>alert(1)</script> & \"aspas\"\n\n## Dois\n\n## Três");

    $response = $this->get(route('blog.show', $post))->assertOk();

    expect($response->getContent())->not->toContain('<script>alert(1)</script>');

    [$html, $toc] = TableOfContents::render($post);

    expect($html)->toContain('<h2 id="use-alert1-aspas">Use &lt;script&gt;alert(1)&lt;/script&gt; &amp; &quot;aspas&quot;')
        ->and($toc[0]['text'])->toBe('Use alert(1) & "aspas"');
});

it('falls back to a generic id for headings without sluggable text', function () {
    [$html, $toc] = TableOfContents::render(tocPost("## 🚀\n\n## 🎉\n\n## Fim"));

    expect(array_column($toc, 'id'))->toBe(['section', 'section-2', 'fim'])
        ->and($html)->toContain('<h2 id="section-2">');
});

it('keeps the anchors when the body is split for the mid-article CTA', function () {
    config(['blog.mid_cta_view' => 'blog::components.squiggle']);
    $post = tocPost(TOC_BODY);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSeeInOrder(['<h2 id="como-comecar">', 'data-blog-mid-cta', '<h2 id="perguntas-frequentes">'], false);
});

it('leaves the plain rendered body and the feed without anchors', function () {
    $post = tocPost(TOC_BODY);

    expect($post->renderedBody())->toContain('<h2>Como começar</h2>');

    config(['blog.feed.full_content' => true]);

    $this->get(route('blog.feed'))
        ->assertOk()
        ->assertDontSee('blog-heading-anchor', false);
});
