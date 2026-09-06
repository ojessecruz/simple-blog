<?php

declare(strict_types=1);

use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\Seo;
use Jessecruz\SimpleBlog\Tests\Stubs\User;

function publishedPost(array $attributes = []): Post
{
    $category = PostCategory::firstOrCreate(['slug' => 'produto'], ['name' => 'Produto', 'description' => 'Novidades do produto.']);

    return Post::create(array_merge([
        'slug' => 'meu-post',
        'title' => 'Meu Post',
        'excerpt' => 'Resumo curto do post.',
        'body' => 'Conteúdo.',
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ], $attributes));
}

function titles(string $html): array
{
    preg_match_all('#<title>(.*?)</title>#s', $html, $matches);

    return $matches[1];
}

beforeEach(function () {
    config()->set('app.name', 'Acme');
});

it('emits exactly one title on index, category and post pages', function () {
    $post = publishedPost();

    expect(titles($this->get(route('blog.index'))->getContent()))->toBe(['Blog | Acme'])
        ->and(titles($this->get(route('blog.category', 'produto'))->getContent()))->toBe(['Produto | Blog | Acme'])
        ->and(titles($this->get(route('blog.show', $post))->getContent()))->toBe(['Meu Post | Blog | Acme']);
});

it('uses the configured blog name, index title and description', function () {
    config()->set('blog.seo', [
        'blog_name' => 'Blog da Acme',
        'index_title' => 'Blog da Acme | Histórias de quem faz',
        'description' => 'Artigos sobre o dia a dia da Acme.',
    ]);
    $post = publishedPost();
    $uncategorised = PostCategory::create(['slug' => 'sem-descricao', 'name' => 'Sem descrição']);

    $this->get(route('blog.index'))
        ->assertSee('<title>Blog da Acme | Histórias de quem faz</title>', false)
        ->assertSee('<meta name="description" content="Artigos sobre o dia a dia da Acme.">', false);

    $this->get(route('blog.category', 'produto'))
        ->assertSee('<title>Produto | Blog da Acme</title>', false)
        ->assertSee('<meta name="description" content="Novidades do produto.">', false);

    $this->get(route('blog.category', $uncategorised))
        ->assertSee('<meta name="description" content="Artigos sobre o dia a dia da Acme.">', false);

    $this->get(route('blog.show', $post))
        ->assertSee('<title>Meu Post | Blog da Acme</title>', false);
});

it('prefers meta_title and meta_description on posts', function () {
    $post = publishedPost(['meta_title' => 'Título otimizado', 'meta_description' => 'Descrição otimizada.']);

    $this->get(route('blog.show', $post))
        ->assertSee('<title>Título otimizado | Blog | Acme</title>', false)
        ->assertSee('<meta name="description" content="Descrição otimizada.">', false)
        ->assertSee('<meta property="og:title" content="Meu Post">', false);
});

it('falls back to the excerpt for the post description', function () {
    $post = publishedPost();

    $this->get(route('blog.show', $post))
        ->assertSee('<meta name="description" content="Resumo curto do post.">', false);
});

it('resolves the social image from og_image, then cover, then the configured default', function () {
    config()->set('blog.seo.image', 'https://acme.test/default.png');

    $withOg = publishedPost(['slug' => 'a', 'og_image' => 'https://acme.test/og.png', 'cover_image' => 'https://acme.test/cover.png']);
    $withCover = publishedPost(['slug' => 'b', 'cover_image' => 'https://acme.test/cover.png']);
    $plain = publishedPost(['slug' => 'c']);

    expect(Seo::postImage($withOg))->toBe('https://acme.test/og.png')
        ->and(Seo::postImage($withCover))->toBe('https://acme.test/cover.png')
        ->and(Seo::postImage($plain))->toBe('https://acme.test/default.png');

    $this->get(route('blog.index'))
        ->assertSee('<meta property="og:image" content="https://acme.test/default.png">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

it('omits description and image tags when nothing is configured', function () {
    $html = $this->get(route('blog.index'))->getContent();

    expect($html)->not->toContain('name="description"')
        ->and($html)->not->toContain('og:image');
});

it('emits a complete Article JSON-LD with author, publisher, image and dates', function () {
    config()->set('blog.seo.publisher_logo', 'https://acme.test/logo.png');
    $author = User::create(['name' => 'Ana Souza']);
    $post = publishedPost(['author_id' => $author->id, 'cover_image' => 'https://acme.test/cover.png']);

    $html = $this->get(route('blog.show', $post))->getContent();

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
    $schema = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

    expect($schema['@type'])->toBe('Article')
        ->and($schema['headline'])->toBe('Meu Post')
        ->and($schema['author'])->toBe(['@type' => 'Person', 'name' => 'Ana Souza'])
        ->and($schema['publisher'])->toBe([
            '@type' => 'Organization',
            'name' => 'Acme',
            'url' => url('/'),
            'logo' => ['@type' => 'ImageObject', 'url' => 'https://acme.test/logo.png'],
        ])
        ->and($schema['image'])->toBe(['https://acme.test/cover.png'])
        ->and($schema['mainEntityOfPage']['@id'])->toBe($post->url())
        ->and($schema['datePublished'])->toBe($post->published_at->toIso8601String())
        ->and($schema['dateModified'])->toBe($post->updated_at->toIso8601String())
        ->and($html)->toContain('<meta property="article:modified_time" content="'.$post->updated_at->toIso8601String().'">');
});

it('adds canonical, og:url, og:site_name and og:locale in the default layout', function () {
    $post = publishedPost();

    $this->get(route('blog.show', $post))
        ->assertSee('<link rel="canonical" href="'.route('blog.show', $post).'">', false)
        ->assertSee('<meta property="og:url" content="'.route('blog.show', $post).'">', false)
        ->assertSee('<meta property="og:site_name" content="Acme">', false)
        ->assertSee('<meta property="og:locale" content="'.app()->getLocale().'">', false);
});

it('does not emit JSON-LD on the admin preview of a draft', function () {
    $admin = User::create(['name' => 'Admin']);
    $post = publishedPost(['published_at' => null]);

    $this->actingAs($admin)
        ->get(route('blog.admin.preview', $post))
        ->assertOk()
        ->assertDontSee('application/ld+json', false);
});
