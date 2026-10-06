<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Tests\Stubs\User;

it('renders the public index with published posts', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    Post::create([
        'slug' => 'visivel',
        'title' => 'Post Visível',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ]);
    Post::create([
        'slug' => 'rascunho',
        'title' => 'Post Rascunho',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => null,
    ]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Post Visível')
        ->assertDontSee('Post Rascunho');
});

it('renders a published post by slug with markdown converted to html', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create([
        'slug' => 'meu-post',
        'title' => 'Meu Post',
        'excerpt' => 'Resumo curto',
        'body' => "## Cabeçalho\n\nUm parágrafo com **negrito**.",
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('<h2>Cabeçalho</h2>', false)
        ->assertSee('<strong>negrito</strong>', false);
});

it('returns 404 for unpublished posts on the public route', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create([
        'slug' => 'rascunho',
        'title' => 'R',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => null,
    ]);

    $this->get(route('blog.show', $post))->assertNotFound();
});

it('renders the preview route for unpublished posts (no isPublished check)', function () {
    $admin = User::create(['name' => 'Admin']);
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create([
        'slug' => 'rascunho-secreto',
        'title' => 'Rascunho Secreto',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('blog.admin.preview', $post))
        ->assertOk()
        ->assertSee('Rascunho Secreto');
});

it('does not increment views_count when previewing', function () {
    $admin = User::create(['name' => 'Admin']);
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create([
        'slug' => 'p',
        'title' => 'P',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'views_count' => 0,
    ]);

    $this->actingAs($admin)->get(route('blog.admin.preview', $post));

    expect($post->fresh()->views_count)->toBe(0);
});

it('filters posts by category', function () {
    $a = PostCategory::create(['slug' => 'a', 'name' => 'Alpha']);
    $b = PostCategory::create(['slug' => 'b', 'name' => 'Beta']);
    Post::create(['slug' => 'pa', 'title' => 'Post Alpha', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $a->id, 'published_at' => now()->subDay()]);
    Post::create(['slug' => 'pb', 'title' => 'Post Beta', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $b->id, 'published_at' => now()->subDay()]);

    $this->get(route('blog.category', $a))
        ->assertOk()
        ->assertSee('Post Alpha')
        ->assertDontSee('Post Beta');
});

it('renders related links with high-contrast, always-underlined classes', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create(['slug' => 'principal', 'title' => 'Principal', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => now()->subDays(2)]);
    Post::create(['slug' => 'irmao', 'title' => 'Post Irmão', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => now()->subDay()]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('Post Irmão')
        ->assertSee('text-gray-900 underline decoration-emerald-500/80', false)
        ->assertSee('dark:text-gray-100', false);
});

it('does not depend on arbitrary opacity utilities for the index header pattern', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('opacity: 0.05', false)
        ->assertDontSee('opacity-[0.05]', false);
});

it('reserves the cover slot on the index whether or not a post has a cover', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    Post::create(['slug' => 'com-capa', 'title' => 'Com Capa', 'excerpt' => 'r', 'body' => 'c', 'cover_image' => 'https://example.com/capa.jpg', 'blog_category_id' => $category->id, 'published_at' => now()->subDay()]);
    Post::create(['slug' => 'sem-capa', 'title' => 'Sem Capa', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => now()->subDay()]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('src="https://example.com/capa.jpg" alt="Com Capa"', false)
        ->assertSee('data-cover-placeholder', false)
        ->assertSee('aspect-[16/10] w-full object-cover', false);
});

it('renders the listing as a responsive multi-column grid', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    Post::create(['slug' => 'p', 'title' => 'P', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => now()->subDay()]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('max-w-6xl', false)
        ->assertSee('grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3', false);
});

it('shows the ICP blurb on the index header in pt_BR', function () {
    config()->set('blog.locale', 'pt_BR');

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Últimas publicações')
        ->assertSee('Dicas práticas de agenda, clientes, WhatsApp e gestão para quem vive de atender');
});

it('prefers the category description over the index blurb', function () {
    config()->set('blog.locale', 'pt_BR');
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão', 'description' => 'Tudo sobre gestão.']);

    $this->get(route('blog.category', $category))
        ->assertOk()
        ->assertSee('Tudo sobre gestão.')
        ->assertDontSee('Dicas práticas de agenda');
});

it('formats meta dates in Portuguese when the locale is pt_BR', function () {
    config()->set('blog.locale', 'pt_BR');
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = Post::create(['slug' => 'p', 'title' => 'P', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => '2026-03-05 10:00:00']);

    $this->get(route('blog.index'))->assertOk()->assertSee('5 de março de 2026');
    $this->get(route('blog.show', $post))->assertOk()->assertSee('5 de março de 2026');
});

it('keeps the English date format when the locale is en', function () {
    config()->set('blog.locale', 'en');
    $category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    Post::create(['slug' => 'p', 'title' => 'P', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => '2026-03-05 10:00:00']);

    $this->get(route('blog.index'))->assertOk()->assertSee('Mar 5, 2026');
});

function postWithBody(string $body): Post
{
    View::addLocation(__DIR__.'/../Stubs/views');
    $category = PostCategory::firstOrCreate(['slug' => 'g'], ['name' => 'Gestão']);

    return Post::create(['slug' => 'cta', 'title' => 'Post CTA', 'excerpt' => 'r', 'body' => $body, 'blog_category_id' => $category->id, 'published_at' => now()->subDay()]);
}

it('renders the mid-article CTA once, between body sections', function () {
    config(['blog.mid_cta_view' => 'mid-cta']);
    $post = postWithBody("Introdução.\n\n## Primeira\n\nTexto um.\n\n## Segunda\n\nTexto dois.");

    $html = $this->get(route('blog.show', $post))->assertOk()->getContent();

    expect(substr_count($html, 'MID CTA for Post CTA'))->toBe(1)
        ->and(substr_count($html, 'data-blog-mid-cta'))->toBe(1)
        ->and(substr_count($html, '<div class="blog-content">'))->toBe(2);

    $this->get(route('blog.show', $post))->assertSeeInOrder([
        '<div class="blog-content">',
        '<h2>Primeira</h2>',
        'Texto um.',
        'data-blog-mid-cta',
        'MID CTA for Post CTA',
        '<div class="blog-content">',
        '<h2>Segunda</h2>',
        'Texto dois.',
    ], false);
});

it('does not render the mid-article CTA when mid_cta_view is null', function () {
    config(['blog.mid_cta_view' => null]);
    $post = postWithBody("Introdução.\n\n## Primeira\n\nTexto um.\n\n## Segunda\n\nTexto dois.");

    $html = $this->get(route('blog.show', $post))->assertOk()->assertSee('<h2>Segunda</h2>', false)->getContent();

    expect($html)->not->toContain('data-blog-mid-cta')
        ->and(substr_count($html, '<div class="blog-content">'))->toBe(1);
});

it('does not render the mid-article CTA on short posts', function () {
    config(['blog.mid_cta_view' => 'mid-cta']);
    $post = postWithBody("Um parágrafo só.\n\nE outro.");

    $html = $this->get(route('blog.show', $post))->assertOk()->assertSee('E outro.')->getContent();

    expect($html)->not->toContain('MID CTA')
        ->and(substr_count($html, '<div class="blog-content">'))->toBe(1);
});

it('renders the end cta_view independently from mid_cta_view', function () {
    config(['blog.cta_view' => 'end-cta', 'blog.mid_cta_view' => null]);
    $post = postWithBody("Introdução.\n\n## Primeira\n\nTexto um.\n\n## Segunda\n\nTexto dois.");

    $this->get(route('blog.show', $post))->assertOk()
        ->assertSee('END CTA for Post CTA')
        ->assertDontSee('MID CTA');

    config(['blog.mid_cta_view' => 'mid-cta']);

    $this->get(route('blog.show', $post))->assertOk()
        ->assertSeeInOrder(['MID CTA for Post CTA', '<h2>Segunda</h2>', 'END CTA for Post CTA'], false);
});
