<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\Seo;

afterEach(fn () => Carbon::setTestNow());

/**
 * @return array<string, string|null> lastmod by loc
 */
function sitemapUrls(string $xml): array
{
    $urlset = simplexml_load_string($xml);

    expect($urlset)->toBeInstanceOf(SimpleXMLElement::class);

    $urls = [];
    foreach ($urlset->url as $url) {
        $urls[(string) $url->loc] = isset($url->lastmod) ? (string) $url->lastmod : null;
    }

    return $urls;
}

it('lists the index, categories with published posts and published posts', function () {
    $news = PostCategory::create(['slug' => 'news', 'name' => 'News']);
    $guides = PostCategory::create(['slug' => 'guides', 'name' => 'Guides']);
    $empty = PostCategory::create(['slug' => 'empty', 'name' => 'Empty']);

    $published = Post::create(['slug' => 'live', 'title' => 'Live', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $news->id, 'published_at' => now()->subDay()]);
    Post::create(['slug' => 'draft', 'title' => 'Draft', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $guides->id, 'published_at' => null]);
    Post::create(['slug' => 'later', 'title' => 'Later', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $guides->id, 'published_at' => now()->addDay()]);

    $response = $this->get(route('blog.sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    expect($response->getContent())->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->and(array_keys(sitemapUrls($response->getContent())))->toBe([
            route('blog.index'),
            route('blog.category', $news),
            $published->url(),
        ])
        ->and(sitemapUrls($response->getContent()))->not->toHaveKey(route('blog.category', $empty));
});

it('dates each URL by its latest change', function () {
    $category = PostCategory::create(['slug' => 'news', 'name' => 'News']);

    Carbon::setTestNow('2026-10-01 10:00:00');
    $edited = Post::create(['slug' => 'edited', 'title' => 'Edited', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => '2026-09-01 08:00:00']);
    // Saved on Oct 1, scheduled to go live on Oct 15.
    $scheduled = Post::create(['slug' => 'scheduled', 'title' => 'Scheduled', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => '2026-10-15 09:00:00']);

    Carbon::setTestNow('2026-10-20 12:00:00');
    $urls = sitemapUrls($this->get(route('blog.sitemap'))->getContent());

    expect($urls[$edited->url()])->toBe('2026-10-01T10:00:00+00:00')
        ->and($urls[$scheduled->url()])->toBe('2026-10-15T09:00:00+00:00')
        ->and($urls[route('blog.category', $category)])->toBe('2026-10-15T09:00:00+00:00')
        ->and($urls[route('blog.index')])->toBe('2026-10-15T09:00:00+00:00');
});

it('is a valid sitemap with no posts', function () {
    expect(sitemapUrls($this->get(route('blog.sitemap'))->getContent()))
        ->toBe([route('blog.index') => null]);
});

it('never dates a scheduled post as modified before it was published', function () {
    $category = PostCategory::create(['slug' => 'news', 'name' => 'News']);

    Carbon::setTestNow('2026-10-01 10:00:00');
    $post = Post::create(['slug' => 'scheduled', 'title' => 'Scheduled', 'excerpt' => 'r', 'body' => 'c', 'blog_category_id' => $category->id, 'published_at' => '2026-10-15 09:00:00']);

    Carbon::setTestNow('2026-10-20 12:00:00');
    $schema = Seo::articleSchema($post);

    expect(Seo::lastModified($post)->toIso8601String())->toBe('2026-10-15T09:00:00+00:00')
        ->and($schema['dateModified'])->toBe($schema['datePublished']);
});

it('registers no sitemap route when disabled', function () {
    $this->bootConfig = ['sitemap.enabled' => false];
    $this->refreshApplication();

    expect(Route::has('blog.sitemap'))->toBeFalse();

    $this->get('/blog/sitemap.xml')->assertNotFound();
});
