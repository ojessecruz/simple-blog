<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\Share;
use Jessecruz\SimpleBlog\Tests\Stubs\User;

function sharePost(array $attributes = []): Post
{
    $category = PostCategory::firstOrCreate(['slug' => 'g'], ['name' => 'Gestão']);

    return Post::create(array_merge([
        'slug' => 'meu-post',
        'title' => 'Meu Post & Cia',
        'excerpt' => 'Resumo curto',
        'body' => 'c',
        'blog_category_id' => $category->id,
        'published_at' => now()->subDay(),
    ], $attributes));
}

it('renders the default share buttons with encoded share links', function () {
    $post = sharePost();

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('data-blog-share', false)
        ->assertSee(Share::url('whatsapp', $post))
        ->assertSee(Share::url('x', $post))
        ->assertSee(Share::url('linkedin', $post))
        ->assertSee(Share::url('facebook', $post))
        ->assertSee('<button type="button" data-blog-share-copy', false)
        ->assertSee('<button type="button" hidden data-blog-share-native', false)
        ->assertDontSee('data-blog-share-link="telegram"', false)
        ->assertDontSee('data-blog-share-link="email"', false);

    expect(Share::url('whatsapp', $post))
        ->toBe('https://wa.me/?text=Meu%20Post%20%26%20Cia%20-%20http%3A%2F%2Flocalhost%2Fblog%2Fmeu-post')
        ->and(Share::url('facebook', $post))
        ->toBe('https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Flocalhost%2Fblog%2Fmeu-post');
});

it('renders the networks in the configured order, ignoring unknown and duplicate entries', function () {
    config(['blog.share.networks' => ['email', 'telegram', 'myspace', 'email', 42]]);
    $post = sharePost();

    expect(Share::networks())->toBe(['email', 'telegram']);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSeeInOrder(['data-blog-share-link="email"', 'data-blog-share-link="telegram"'], false)
        ->assertDontSee('data-blog-share-link="whatsapp"', false)
        ->assertDontSee('<button type="button" data-blog-share-copy', false);
});

it('hides the share block when no network is enabled', function () {
    config(['blog.share.networks' => []]);
    $post = sharePost();

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertDontSee('data-blog-share', false);
});

it('does not render share buttons on the admin preview of a draft', function () {
    $post = sharePost(['published_at' => null]);

    $this->actingAs(User::create(['name' => 'Admin']))
        ->get(route('blog.admin.preview', $post))
        ->assertOk()
        ->assertSee('Meu Post &amp; Cia', false)
        ->assertDontSee('data-blog-share', false);
});

it('opens share links in a new tab, except email', function () {
    config(['blog.share.networks' => ['whatsapp', 'email']]);
    $post = sharePost();

    $html = $this->get(route('blog.show', $post))->assertOk()->getContent();

    expect($html)->toMatch('/data-blog-share-link="whatsapp"\s+target="_blank" rel="noopener noreferrer"/')
        ->and($html)->not->toMatch('/data-blog-share-link="email"\s+target="_blank"/');
});

it('credits the configured X handle without the leading @', function () {
    $post = sharePost();

    expect(Share::url('x', $post))->not->toContain('via=');

    config(['blog.share.x_via' => '@usehoot']);

    expect(Share::url('x', $post))
        ->toBe('https://twitter.com/intent/tweet?url=http%3A%2F%2Flocalhost%2Fblog%2Fmeu-post&text=Meu%20Post%20%26%20Cia&via=usehoot');
});

it('builds a mailto link with %20-encoded subject and body', function () {
    $post = sharePost();

    expect(Share::url('email', $post))
        ->toBe('mailto:?subject=Meu%20Post%20%26%20Cia&body=Resumo%20curto%0A%0Ahttp%3A%2F%2Flocalhost%2Fblog%2Fmeu-post');
});

it('has no share URL for the client-side buttons', function () {
    $post = sharePost();

    expect(Share::url('copy', $post))->toBeNull()
        ->and(Share::url('native', $post))->toBeNull();
});

it('translates the share labels', function () {
    config(['blog.locale' => 'pt_BR', 'blog.share.networks' => ['whatsapp', 'email', 'copy', 'native']]);
    $post = sharePost();

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('aria-label="Compartilhar"', false)
        ->assertSee('aria-label="Compartilhar no WhatsApp"', false)
        ->assertSee('aria-label="Enviar por e-mail"', false)
        ->assertSee('aria-label="Copiar link"', false)
        ->assertSee('data-copied="Link copiado"', false)
        ->assertSee('aria-label="Mais opções de compartilhamento"', false);
});

it('emits the share script once per page, with the CSP nonce when one is set', function () {
    Vite::useCspNonce('share-nonce');
    $post = sharePost();

    $html = Blade::render('<x-blog::share :post="$post" /><x-blog::share :post="$post" />', ['post' => $post]);

    expect(substr_count($html, 'data-blog-share role="group"'))->toBe(2)
        ->and(substr_count($html, '<script'))->toBe(1)
        ->and($html)->toContain('nonce="share-nonce"');
});
