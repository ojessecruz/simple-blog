<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Tests\Stubs\User;

beforeEach(function () {
    $this->category = PostCategory::create(['slug' => 'scheduling', 'name' => 'Scheduling']);
});

function apiPostPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'How to reduce no-shows',
        'excerpt' => 'Why clients miss appointments and what to do about it.',
        'body' => str_repeat('word ', 450),
        'category' => 'scheduling',
    ], $overrides);
}

it('does not register the api routes unless enabled', function () {
    $this->enablesBlogApi = false;
    $this->refreshApplication();

    expect(Route::has('blog.api.posts.index'))->toBeFalse();
    $this->getJson('/api/blog/posts')->assertNotFound();
});

it('rejects requests without the api token', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with([
    ['GET', '/api/blog/posts'],
    ['POST', '/api/blog/posts'],
    ['GET', '/api/blog/categories'],
    ['POST', '/api/blog/categories'],
]);

it('rejects requests with a wrong token', function () {
    $this->withToken('wrong')->getJson('/api/blog/posts')->assertUnauthorized();
});

it('rejects every request when no token is configured', function () {
    config()->set('blog.api.token', null);

    $this->withToken('')->getJson('/api/blog/posts')->assertUnauthorized();
});

it('creates a draft post with slug and reading time derived from the payload', function () {
    $this->withToken('blog-token')
        ->postJson('/api/blog/posts', apiPostPayload(['keywords' => ['no-shows', 'reminders']]))
        ->assertCreated()
        ->assertJsonPath('data.slug', 'how-to-reduce-no-shows')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.reading_time', 3)
        ->assertJsonPath('data.category.slug', 'scheduling')
        ->assertJsonPath('data.keywords', ['no-shows', 'reminders']);

    $post = Post::sole();

    expect($post->blog_category_id)->toBe($this->category->id)
        ->and($post->published_at)->toBeNull();
});

it('creates a published post when published_at is in the past', function () {
    $this->withToken('blog-token')
        ->postJson('/api/blog/posts', apiPostPayload([
            'slug' => 'no-shows',
            'published_at' => now()->subHour()->toIso8601String(),
            'reading_time' => 7,
        ]))
        ->assertCreated()
        ->assertJsonPath('data.slug', 'no-shows')
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.reading_time', 7);
});

it('validates required fields and the category on create', function () {
    $this->withToken('blog-token')
        ->postJson('/api/blog/posts', ['category' => 'missing'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'slug', 'excerpt', 'body', 'category']);
});

it('rejects a duplicated slug', function () {
    $this->withToken('blog-token')->postJson('/api/blog/posts', apiPostPayload())->assertCreated();

    $this->withToken('blog-token')
        ->postJson('/api/blog/posts', apiPostPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('validates author_id against the configured author model', function () {
    $author = User::create(['name' => 'Ada Lovelace']);

    $this->withToken('blog-token')
        ->postJson('/api/blog/posts', apiPostPayload(['author_id' => $author->id + 1]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['author_id']);

    $this->withToken('blog-token')
        ->postJson('/api/blog/posts', apiPostPayload(['author_id' => $author->id]))
        ->assertCreated()
        ->assertJsonPath('data.author_id', $author->id);
});

it('updates only the fields sent', function () {
    $post = Post::create([
        'slug' => 'draft',
        'title' => 'Old title',
        'excerpt' => 'Excerpt',
        'body' => 'Body',
        'blog_category_id' => $this->category->id,
        'reading_time' => 4,
    ]);

    $this->withToken('blog-token')
        ->patchJson("/api/blog/posts/{$post->slug}", [
            'title' => 'New title',
            'slug' => 'draft',
            'published_at' => now()->addDay()->toIso8601String(),
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'New title')
        ->assertJsonPath('data.status', 'scheduled')
        ->assertJsonPath('data.body', 'Body')
        ->assertJsonPath('data.reading_time', 4);
});

it('lists posts without the body and shows a single post with it', function () {
    $this->withToken('blog-token')->postJson('/api/blog/posts', apiPostPayload())->assertCreated();

    $this->withToken('blog-token')
        ->getJson('/api/blog/posts')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonMissingPath('data.0.body');

    $this->withToken('blog-token')
        ->getJson('/api/blog/posts/how-to-reduce-no-shows')
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'How to reduce no-shows')
        ->assertJsonStructure(['data' => ['body']]);
});

it('lists and creates categories', function () {
    $this->withToken('blog-token')
        ->postJson('/api/blog/categories', ['name' => 'Business Management'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'business-management');

    $this->withToken('blog-token')
        ->getJson('/api/blog/categories')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'business-management');
});
