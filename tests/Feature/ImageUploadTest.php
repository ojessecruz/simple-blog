<?php

declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Jessecruz\SimpleBlog\Livewire\Admin\PostForm;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\Images;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $this->disk = Storage::fake('public', ['url' => 'https://cdn.test/storage']);
});

function imagePost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'slug' => 'p-'.uniqid(),
        'title' => 'Post',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => PostCategory::first()->id,
        'published_at' => now()->subDay(),
    ], $attributes));
}

/** A file already uploaded to the images directory, as the form stores it. */
function storedImage(string $name = 'capa'): string
{
    $path = 'blog/'.$name.'-'.Str::lower(Str::random(8)).'.jpg';
    Storage::disk('public')->put($path, UploadedFile::fake()->image('x.jpg')->getContent());

    return $path;
}

function newPostForm(): Testable
{
    return Livewire::test(PostForm::class)
        ->set('title', 'Novo Post')
        ->set('excerpt', 'Resumo')
        ->set('body', 'Texto')
        ->set('blog_category_id', PostCategory::first()->id);
}

it('uploads the cover and social images on save, named after the post', function () {
    newPostForm()
        ->set('coverUpload', UploadedFile::fake()->image('Foto Original.jpg', 1200, 630))
        ->set('ogUpload', UploadedFile::fake()->image('social.png', 1200, 630))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('blog.admin.index'));

    $post = Post::where('slug', 'novo-post')->firstOrFail();
    $cover = $post->getRawOriginal('cover_image');
    $og = $post->getRawOriginal('og_image');

    expect($cover)->toMatch('#^blog/novo-post-[a-z0-9]{8}\.jpg$#')
        ->and($og)->toMatch('#^blog/novo-post-[a-z0-9]{8}\.png$#')
        ->and($post->cover_image)->toBe('https://cdn.test/storage/'.$cover)
        ->and($post->og_image)->toBe('https://cdn.test/storage/'.$og);
    $this->disk->assertExists([$cover, $og]);
});

it('shows uploaded images on the post page and in its social tags', function () {
    $post = imagePost(['cover_image' => storedImage(), 'og_image' => $og = storedImage('social')]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('src="https://cdn.test/storage/'.$post->getRawOriginal('cover_image').'"', false)
        ->assertSee('<meta property="og:image" content="https://cdn.test/storage/'.$og.'">', false);

    $this->getJson('/api/blog/posts/'.$post->slug, ['Authorization' => 'Bearer blog-token'])
        ->assertJsonPath('data.og_image', 'https://cdn.test/storage/'.$og);
});

it('refuses files that are not JPG, PNG, GIF or WebP images', function (Closure $file) {
    newPostForm()
        ->set('coverUpload', $file())
        ->assertHasErrors('coverUpload')
        ->assertSet('coverUpload', null);

    expect($this->disk->allFiles('blog'))->toBe([]);
})->with([
    'PDF' => fn () => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    'SVG' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
]);

it('checks the content of an upload, not its name', function () {
    // A real upload: test fakes report the MIME type of their name.
    $html = tempnam(sys_get_temp_dir(), 'blog');
    file_put_contents($html, '<html><script>alert(1)</script></html>');
    $jpeg = tempnam(sys_get_temp_dir(), 'blog');
    file_put_contents($jpeg, UploadedFile::fake()->image('x.jpg')->getContent());

    $validates = fn (string $path) => Validator::make(
        ['image' => new UploadedFile($path, 'capa.jpg', null, null, true)],
        ['image' => Images::uploadRules()],
    )->passes();

    expect($validates($html))->toBeFalse()
        ->and($validates($jpeg))->toBeTrue();

    unlink($html);
    unlink($jpeg);
});

it('refuses images over images.max_size', function () {
    config()->set('blog.images.max_size', 100);

    newPostForm()
        ->set('coverUpload', UploadedFile::fake()->image('grande.jpg')->size(101))
        ->assertHasErrors(['coverUpload' => 'max'])
        ->assertSet('coverUpload', null);
});

it('previews a picked image before saving', function () {
    $form = newPostForm()->set('coverUpload', UploadedFile::fake()->image('capa.jpg'));

    expect($form->instance()->imagePreview('cover_image'))->toBeString()
        ->and($form->instance()->imagePreview('og_image'))->toBeNull()
        ->and($form->instance()->imagePreview('title'))->toBeNull();
});

it('keeps an uploaded image when the post is saved again', function () {
    $post = imagePost(['cover_image' => $path = storedImage()]);

    Livewire::test(PostForm::class, ['post' => $post])
        ->assertSet('cover_image', 'https://cdn.test/storage/'.$path)
        ->set('title', 'Outro título')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->getRawOriginal('cover_image'))->toBe($path);
    $this->disk->assertExists($path);
});

it('stores an upload once when saving and previewing before saving', function () {
    $form = newPostForm()
        ->set('coverUpload', UploadedFile::fake()->image('capa.jpg'))
        ->call('preview')
        ->assertSet('coverUpload', null);

    $path = Post::firstOrFail()->getRawOriginal('cover_image');
    $form->assertSet('cover_image', 'https://cdn.test/storage/'.$path)->call('save')->assertHasNoErrors();

    expect($this->disk->allFiles('blog'))->toBe([$path]);
});

it('deletes the previous upload when the image is replaced, removed or set to a URL', function (Closure $change) {
    $post = imagePost(['cover_image' => $old = storedImage()]);

    $change(Livewire::test(PostForm::class, ['post' => $post]))->call('save')->assertHasNoErrors();

    $this->disk->assertMissing($old);
})->with([
    'replaced' => fn ($form) => $form->set('coverUpload', UploadedFile::fake()->image('nova.jpg')),
    'removed' => fn ($form) => $form->call('clearImage', 'cover_image')->assertSet('cover_image', null),
    'URL' => fn ($form) => $form->set('cover_image', 'https://images.example.com/capa.jpg'),
]);

it('deletes the images of a deleted post', function () {
    $post = imagePost(['cover_image' => $cover = storedImage(), 'og_image' => $og = storedImage('social')]);

    $post->delete();

    $this->disk->assertMissing([$cover, $og]);
});

it('keeps an upload that another post still uses', function () {
    $post = imagePost(['cover_image' => $path = storedImage()]);
    $copy = imagePost(['og_image' => $post->cover_image]);

    expect($copy->getRawOriginal('og_image'))->toBe($path);

    $post->delete();
    $this->disk->assertExists($path);

    $copy->update(['og_image' => null]);
    $this->disk->assertMissing($path);
});

it('keeps the upload when the API gets back the URL it returned', function () {
    $post = imagePost(['cover_image' => $path = storedImage()]);
    $url = $this->getJson('/api/blog/posts/'.$post->slug, ['Authorization' => 'Bearer blog-token'])->json('data.cover_image');

    $this->withToken('blog-token')
        ->patchJson('/api/blog/posts/'.$post->slug, ['title' => 'Novo', 'cover_image' => $url])
        ->assertOk();

    expect($post->fresh()->getRawOriginal('cover_image'))->toBe($path);
    $this->disk->assertExists($path);
});

it('never touches files outside the images directory', function (string $url) {
    $this->disk->put('avatars/ana.jpg', 'x');
    $post = imagePost(['cover_image' => $url]);

    expect($post->getRawOriginal('cover_image'))->toBe($url);

    $post->delete();
    $this->disk->assertExists('avatars/ana.jpg');
})->with([
    'parent segment' => 'https://cdn.test/storage/blog/../avatars/ana.jpg',
    'empty segment' => 'https://cdn.test/storage/blog//../avatars/ana.jpg',
    'backslash' => 'https://cdn.test/storage/blog/..\\avatars\\ana.jpg',
]);

it('ignores stored paths outside the images directory', function () {
    $this->disk->put('avatars/ana.jpg', 'x');
    $post = imagePost();
    $post->getConnection()->table('blog_posts')->where('id', $post->id)->update(['cover_image' => 'avatars/ana.jpg']);

    $post->fresh()->delete();

    $this->disk->assertExists('avatars/ana.jpg');
});

it('accepts the relative URLs of a disk without a base URL', function () {
    Storage::fake('public');
    $post = imagePost(['cover_image' => $path = storedImage()]);

    expect($post->cover_image)->toBe('/storage/'.$path);

    Livewire::test(PostForm::class, ['post' => $post])->call('save')->assertHasNoErrors();

    expect($post->fresh()->getRawOriginal('cover_image'))->toBe($path);
    $this->get(route('blog.show', $post))
        ->assertSee('<meta property="og:image" content="http://localhost/storage/'.$path.'">', false);
});

it('still rejects image fields that are not URLs', function () {
    newPostForm()
        ->set('cover_image', 'not a url')
        ->set('og_image', 'blog/../../.env')
        ->call('save')
        ->assertHasErrors(['cover_image', 'og_image']);
});

it('shows an error when the disk refuses the file', function () {
    $broken = Mockery::mock(FilesystemAdapter::class);
    $broken->shouldReceive('put')->andReturnFalse();
    config()->set('filesystems.disks.broken', ['driver' => 'local', 'root' => storage_path('broken')]);
    Storage::set('broken', $broken);
    config()->set('blog.images.disk', 'broken');

    newPostForm()
        ->set('coverUpload', UploadedFile::fake()->image('capa.jpg'))
        ->call('save')
        ->assertHasErrors('coverUpload');

    expect(Post::count())->toBe(0);
});

it('takes images by URL only when uploads are off', function (array $config) {
    config()->set($config);

    Livewire::test(PostForm::class)
        ->assertDontSee('type="file"', false)
        ->assertSee('type="url"', false);

    $post = imagePost(['cover_image' => 'https://cdn.test/storage/blog/capa-12345678.jpg']);

    expect($post->getRawOriginal('cover_image'))->toBe('https://cdn.test/storage/blog/capa-12345678.jpg');
})->with([
    'images.enabled off' => [['blog.images.enabled' => false]],
    'disk not in config/filesystems.php' => [['blog.images.disk' => 'missing']],
    'default disk not in config/filesystems.php' => [['filesystems.default' => 'missing']],
]);

it('uploads to the app\'s default disk, in the blog folder', function () {
    config()->set('filesystems.disks.r2', ['driver' => 'local', 'root' => storage_path('r2'), 'url' => 'https://pub-123.r2.dev']);
    config()->set('filesystems.default', 'r2');
    $r2 = Storage::fake('r2', ['url' => 'https://pub-123.r2.dev']);

    newPostForm()->set('coverUpload', UploadedFile::fake()->image('capa.jpg'))->call('save')->assertHasNoErrors();

    $post = Post::firstOrFail();
    $path = $post->getRawOriginal('cover_image');

    expect($path)->toStartWith('blog/novo-post-')
        ->and($post->cover_image)->toBe('https://pub-123.r2.dev/'.$path);
    $r2->assertExists($path);
    $this->disk->assertMissing($path);
});

it('uses the public disk when the default is Laravel\'s private local disk', function () {
    config()->set('filesystems.default', 'local');

    expect(Images::disk())->toBe('public');

    config()->set('blog.images.disk', 'local');

    expect(Images::disk())->toBe('local');
});

it('uses images.disk over the default disk', function () {
    config()->set('filesystems.default', 's3');
    config()->set('blog.images.disk', 'public');

    expect(Images::disk())->toBe('public');
});

it('stores uploads at the root of the disk when the directory is empty', function () {
    config()->set('blog.images.directory', '');

    newPostForm()->set('coverUpload', UploadedFile::fake()->image('capa.jpg'))->call('save')->assertHasNoErrors();

    $post = Post::firstOrFail();
    $path = $post->getRawOriginal('cover_image');

    expect($path)->toMatch('#^novo-post-[a-z0-9]{8}\.jpg$#')
        ->and($post->cover_image)->toBe('https://cdn.test/storage/'.$path);

    $post->update(['cover_image' => $post->cover_image]);
    expect($post->fresh()->getRawOriginal('cover_image'))->toBe($path);

    $post->delete();
    $this->disk->assertMissing($path);
});

it('offers the upload button with the size limit when uploads are on', function () {
    Livewire::test(PostForm::class)
        ->assertSee('type="file"', false)
        ->assertSee('2 MB');

    expect(Images::enabled())->toBeTrue();
});

it('uploads an image picked in the body editor right away and inserts its Markdown', function () {
    $form = newPostForm()
        ->set('bodyUpload', UploadedFile::fake()->image('Foto_da [equipe].jpg'))
        ->assertHasNoErrors()
        ->assertSet('bodyUpload', null);

    $files = $this->disk->allFiles('blog');

    expect($files)->toHaveCount(1)->and($files[0])->toMatch('#^blog/novo-post-[a-z0-9]{8}\.jpg$#');
    $form->assertDispatched('blog-insert-image', markdown: '![Foto da equipe](https://cdn.test/storage/'.$files[0].')');
});

it('names body images "image" before the post has a title', function () {
    Livewire::test(PostForm::class)->set('bodyUpload', UploadedFile::fake()->image('x.png'));

    expect($this->disk->allFiles('blog')[0])->toMatch('#^blog/image-[a-z0-9]{8}\.png$#');
});

it('refuses body images that are not JPG, PNG, GIF or WebP', function () {
    newPostForm()
        ->set('bodyUpload', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))
        ->assertHasErrors('bodyUpload')
        ->assertNotDispatched('blog-insert-image');

    expect($this->disk->allFiles('blog'))->toBe([]);
});

it('deletes a body image once no post links to it', function () {
    $path = storedImage('corpo');
    $url = 'https://cdn.test/storage/'.$path;
    $post = imagePost(['body' => "Texto\n\n![foto]({$url})\n\nFim."]);
    $copy = imagePost(['body' => "Veja: {$url}."]);

    $post->update(['body' => 'Texto sem a imagem.']);
    $this->disk->assertExists($path);

    $copy->delete();
    $this->disk->assertMissing($path);
});

it('deletes the body images of a deleted post', function () {
    $path = storedImage('corpo');
    $post = imagePost(['body' => '![foto](https://cdn.test/storage/'.$path.')']);

    $post->delete();

    $this->disk->assertMissing($path);
});

it('keeps a body image that a post uses as its cover', function () {
    $path = storedImage('corpo');
    $post = imagePost(['body' => '![foto](https://cdn.test/storage/'.$path.')']);
    imagePost(['cover_image' => 'https://cdn.test/storage/'.$path]);

    $post->delete();

    $this->disk->assertExists($path);
});

it('finds only uploads of the images directory in a body', function () {
    $paths = Images::pathsIn(implode("\n", [
        '![a](https://cdn.test/storage/blog/a-12345678.jpg)',
        'https://cdn.test/storage/blog/b-12345678.png.',
        '![c](https://cdn.test/storage/blog/a-12345678.jpg)',
        '![d](https://cdn.test/storage/avatars/d.jpg)',
        '![e](https://other.test/storage/blog/e.jpg)',
        '![f](https://cdn.test/storage/blog/../avatars/f.jpg)',
    ]));

    expect($paths)->toBe(['blog/a-12345678.jpg', 'blog/b-12345678.png']);
});
