<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Jessecruz\SimpleBlog\Livewire\Admin\PostForm;
use Jessecruz\SimpleBlog\Livewire\Admin\PostIndex;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\Dates;
use Jessecruz\SimpleBlog\Tests\Stubs\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
});

afterEach(function () {
    Carbon::setTestNow();
    date_default_timezone_set('UTC');
});

/**
 * Laravel reads app.timezone into PHP's default timezone at boot; switching it
 * mid-test needs both.
 */
function useAppTimezone(string $timezone): void
{
    config(['app.timezone' => $timezone]);
    date_default_timezone_set($timezone);
}

function tzPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'slug' => 'p',
        'title' => 'P',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => PostCategory::first()->id,
    ], $attributes));
}

function storedPublishedAt(): ?string
{
    return DB::table('blog_posts')->value('published_at');
}

function apiCreateWithDate(string $publishedAt): TestResponse
{
    return test()->withToken('blog-token')->postJson('/api/blog/posts', [
        'title' => 'Agendado',
        'excerpt' => 'r',
        'body' => 'c',
        'category' => 'g',
        'published_at' => $publishedAt,
    ]);
}

it('keeps the instant of an API date with an offset in a UTC app', function () {
    apiCreateWithDate('2026-10-22T10:00:00-03:00')
        ->assertCreated()
        ->assertJsonPath('data.published_at', '2026-10-22T13:00:00+00:00');

    expect(storedPublishedAt())->toBe('2026-10-22 13:00:00');
});

it('keeps the instant of a UTC API date in an app running in another timezone', function () {
    useAppTimezone('America/Sao_Paulo');

    apiCreateWithDate('2026-10-22T13:00:00Z')
        ->assertCreated()
        ->assertJsonPath('data.published_at', '2026-10-22T10:00:00-03:00');

    expect(storedPublishedAt())->toBe('2026-10-22 10:00:00');
});

it('still reads API dates without an offset in the app timezone', function () {
    useAppTimezone('America/Sao_Paulo');

    apiCreateWithDate('2026-10-22 13:00:00')
        ->assertCreated()
        ->assertJsonPath('data.published_at', '2026-10-22T13:00:00-03:00');

    expect(storedPublishedAt())->toBe('2026-10-22 13:00:00');
});

it('converts dates in other timezones when saving the model directly', function () {
    tzPost(['published_at' => Carbon::parse('2026-10-22 10:00:00', 'America/Sao_Paulo')]);

    expect(storedPublishedAt())->toBe('2026-10-22 13:00:00')
        ->and(Post::first()->published_at->toIso8601String())->toBe('2026-10-22T13:00:00+00:00');
});

it('saves the admin date typed in the blog timezone', function () {
    config(['blog.timezone' => 'America/Sao_Paulo']);

    Livewire::test(PostForm::class)
        ->set('title', 'Agendado')
        ->set('excerpt', 'r')
        ->set('body', 'c')
        ->set('blog_category_id', $this->category->id)
        ->set('published_at', '2026-10-22T10:00')
        ->call('save');

    expect(storedPublishedAt())->toBe('2026-10-22 13:00:00');

    Livewire::test(PostForm::class, ['post' => Post::first()])
        ->assertSet('published_at', '2026-10-22T10:00');
});

it('pre-fills the admin date with the current time in the blog timezone', function () {
    config(['blog.timezone' => 'America/Sao_Paulo']);
    Carbon::setTestNow('2026-10-22 13:00:00');

    Livewire::test(PostForm::class)
        ->set('isDraft', false)
        ->assertSet('published_at', '2026-10-22T10:00');
});

it('keeps admin dates in the app timezone when no blog timezone is set', function () {
    Livewire::test(PostForm::class)
        ->set('title', 'Agendado')
        ->set('excerpt', 'r')
        ->set('body', 'c')
        ->set('blog_category_id', $this->category->id)
        ->set('published_at', '2026-10-22T10:00')
        ->call('save');

    expect(storedPublishedAt())->toBe('2026-10-22 10:00:00');
});

it('shows the timezone next to the admin date field', function () {
    Livewire::test(PostForm::class)->assertSee('Timezone: UTC');

    config(['blog.timezone' => 'America/Sao_Paulo']);

    Livewire::test(PostForm::class)->assertSee('Timezone: America/Sao_Paulo (UTC-03:00)');
});

it('shows public dates in the blog timezone', function () {
    config(['blog.locale' => 'pt_BR']);
    // 23:30 on Oct 21 in São Paulo
    $post = tzPost(['published_at' => '2026-10-22 02:30:00']);

    $this->get(route('blog.show', $post))->assertOk()->assertSee('22 de outubro de 2026');

    config(['blog.timezone' => 'America/Sao_Paulo']);

    $this->get(route('blog.show', $post))->assertOk()->assertSee('21 de outubro de 2026')->assertDontSee('22 de outubro de 2026');
    $this->get(route('blog.index'))->assertOk()->assertSee('21 de outubro de 2026');
});

it('shows the scheduled preview date translated and in the blog timezone', function () {
    config(['blog.locale' => 'pt_BR', 'blog.timezone' => 'America/Sao_Paulo']);
    Carbon::setTestNow('2026-10-22 12:00:00');
    $post = tzPost(['published_at' => '2026-12-05 12:00:00']);

    $this->actingAs(User::create(['name' => 'Admin']))
        ->get(route('blog.admin.preview', $post))
        ->assertOk()
        ->assertSee('agendado para 5 de dezembro de 2026, 09:00.');
});

it('lists admin dates in the blog timezone and locale format', function () {
    config(['blog.timezone' => 'America/Sao_Paulo']);
    tzPost(['published_at' => '2026-12-05 12:00:00']);

    Livewire::test(PostIndex::class)->assertSee('12/05/2026 09:00');

    app()->setLocale('pt_BR');

    Livewire::test(PostIndex::class)->assertSee('05/12/2026 09:00');
});

it('falls back to the app timezone', function () {
    expect(Dates::timezone())->toBe('UTC');

    useAppTimezone('Europe/Lisbon');
    expect(Dates::timezone())->toBe('Europe/Lisbon');

    config(['blog.timezone' => 'America/Sao_Paulo']);
    expect(Dates::timezone())->toBe('America/Sao_Paulo');
});
