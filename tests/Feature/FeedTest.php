<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jessecruz\SimpleBlog\Livewire\Admin\PostForm;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

function feedPost(array $attributes = []): Post
{
    $category = PostCategory::firstOrCreate(['slug' => 'g'], ['name' => 'Gestão & Cia']);

    return Post::create(array_merge([
        'slug' => 'p-'.uniqid(),
        'title' => 'Post',
        'excerpt' => 'Resumo',
        'body' => 'Corpo',
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ], $attributes));
}

function feedXml(string $xml): SimpleXMLElement
{
    $feed = simplexml_load_string($xml);

    expect($feed)->toBeInstanceOf(SimpleXMLElement::class);

    return $feed;
}

it('serves an RSS 2.0 feed of the latest published posts', function () {
    config(['blog.seo.blog_name' => 'Blog da Hoot', 'blog.seo.description' => 'Guias & novidades']);
    feedPost(['slug' => 'antigo', 'title' => 'Antigo', 'published_at' => now()->subDays(2)]);
    feedPost(['slug' => 'novo', 'title' => 'Novo <3 & cia', 'published_at' => now()->subDay()]);
    feedPost(['slug' => 'rascunho', 'title' => 'Rascunho', 'published_at' => null]);
    feedPost(['slug' => 'agendado', 'title' => 'Agendado', 'published_at' => now()->addDay()]);

    $response = $this->get(route('blog.feed'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

    expect($response->getContent())->toStartWith('<?xml version="1.0" encoding="UTF-8"?>');

    $channel = feedXml($response->getContent())->channel;

    expect((string) $channel->title)->toBe('Blog da Hoot')
        ->and((string) $channel->link)->toBe(route('blog.index'))
        ->and((string) $channel->description)->toBe('Guias & novidades')
        ->and(count($channel->item))->toBe(2)
        ->and((string) $channel->item[0]->title)->toBe('Novo <3 & cia')
        ->and((string) $channel->item[1]->title)->toBe('Antigo');
});

it('describes each item with link, guid, date, author, category and image', function () {
    $post = feedPost([
        'slug' => 'com-capa',
        'excerpt' => 'Um resumo.',
        'cover_image' => 'https://example.com/capa.jpg',
        'published_at' => '2026-10-20 09:30:00',
    ]);

    $item = feedXml($this->get(route('blog.feed'))->getContent())->channel->item[0];

    expect((string) $item->link)->toBe($post->url())
        ->and((string) $item->guid)->toBe($post->url())
        ->and((string) $item->guid['isPermaLink'])->toBe('true')
        ->and((string) $item->pubDate)->toBe('Tue, 20 Oct 2026 09:30:00 +0000')
        ->and((string) $item->description)->toBe('Um resumo.')
        ->and((string) $item->category)->toBe('Gestão & Cia')
        ->and((string) $item->children('http://purl.org/dc/elements/1.1/')->creator)->toBe('Team')
        ->and((string) $item->children('http://search.yahoo.com/mrss/')->content->attributes()['url'])->toBe('https://example.com/capa.jpg');
});

it('limits the number of items', function () {
    config(['blog.feed.limit' => 2]);
    foreach (range(1, 3) as $i) {
        feedPost(['title' => "Post {$i}", 'published_at' => now()->subHours($i)]);
    }

    $channel = feedXml($this->get(route('blog.feed'))->getContent())->channel;

    expect(count($channel->item))->toBe(2)
        ->and((string) $channel->item[1]->title)->toBe('Post 2');
});

it('sends the rendered body only when full_content is on, CDATA-safe', function () {
    // Raw HTML is the only way a literal "]]>" reaches the rendered body.
    config(['blog.markdown.html_input' => 'allow']);
    feedPost(['body' => "## Título\n\n<div>antes ]]> depois</div>"]);

    $item = feedXml($this->get(route('blog.feed'))->getContent())->channel->item[0];
    expect($item->children('http://purl.org/rss/1.0/modules/content/')->encoded)->toHaveCount(0);

    config(['blog.feed.full_content' => true]);

    $item = feedXml($this->get(route('blog.feed'))->getContent())->channel->item[0];
    $content = (string) $item->children('http://purl.org/rss/1.0/modules/content/')->encoded;

    expect($content)->toContain('<h2>Título</h2>')
        ->and($content)->toContain('<div>antes ]]> depois</div>');
});

it('is a valid feed with no posts', function () {
    $channel = feedXml($this->get(route('blog.feed'))->getContent())->channel;

    expect(count($channel->item))->toBe(0)
        ->and((string) $channel->description)->toBe('Blog | Laravel');
});

it('advertises the feed on public pages', function () {
    $post = feedPost();
    $link = '<link rel="alternate" type="application/rss+xml" title="Blog | Laravel" href="'.route('blog.feed').'">';

    $this->get(route('blog.index'))->assertSee($link, false);
    $this->get(route('blog.show', $post))->assertSee($link, false);
});

it('registers no feed route when disabled', function () {
    $this->bootConfig = ['feed.enabled' => false];
    $this->refreshApplication();

    expect(Route::has('blog.feed'))->toBeFalse();

    $this->get('/blog/feed')->assertNotFound();
    $this->get(route('blog.index'))->assertOk()->assertDontSee('application/rss+xml', false);
});

it('reserves the feed slug for posts', function () {
    $category = PostCategory::create(['slug' => 'g', 'name' => 'G']);

    Livewire\Livewire::test(PostForm::class)
        ->set('title', 'Feed')
        ->set('excerpt', 'r')
        ->set('body', 'c')
        ->set('blog_category_id', $category->id)
        ->call('save')
        ->assertHasErrors(['slug' => 'not_in']);

    $this->withToken('blog-token')
        ->postJson('/api/blog/posts', ['title' => 'Feed', 'excerpt' => 'r', 'body' => 'c', 'category' => 'g'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug' => 'This slug is reserved by the blog.']);
});
