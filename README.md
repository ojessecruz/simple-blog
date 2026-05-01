# Simple Blog

A ready-to-use Laravel blog — Medium-style public listing, admin CRUD with Livewire, Markdown with HTML escaping, and draft preview in a new tab. Zero opinions on authentication: you plug it in via Laravel middleware (Gate, guard, or any combo).

## Requirements

- PHP 8.3+
- Laravel 11 / 12 / 13
- Livewire 3.5+
- Tailwind CSS in the consuming app (the package ships Tailwind classes, it does not compile its own CSS)

## Installation

```bash
composer require ojessecruz/simple-blog
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="simple-blog-migrations"
php artisan migrate
```

Publish the config (optional, but recommended):

```bash
php artisan vendor:publish --tag="simple-blog-config"
```

Publish the views (optional, only if you want to customize):

```bash
php artisan vendor:publish --tag="simple-blog-views"
```

## Quickstart

Three steps and your blog is up.

### 1. Protect the admin routes

The package does **not** embed any authorization logic. You plug it in via middleware in `config/blog.php`. Examples:

```php
// Via a specific Gate
'admin_middleware' => ['web', 'auth', 'can:manage-blog'],

// Via a separate guard
'admin_middleware' => ['web', 'auth:admin'],

// Combo of your app's own middleware
'admin_middleware' => ['web', 'auth', 'verified', 'super.admin'],
```

If you go with a Gate, define it as usual in `AuthServiceProvider`:

```php
Gate::define('manage-blog', fn ($user) => $user->is_admin === true);
```

### 2. Configure the author model

Point it to your app's User:

```php
// config/blog.php
'author_model' => App\Models\User::class,
```

And make User implement the `Author` contract:

```php
use Jessecruz\SimpleBlog\Contracts\Author;

class User extends Authenticatable implements Author
{
    public function getBlogAuthorName(): string
    {
        return $this->name;
    }

    public function getBlogAuthorInitials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = array_map(
            fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)),
            array_slice($words, 0, 2),
        );

        return implode('', $initials);
    }
}
```

### 3. Access it

- Public: `https://yourapp.com/blog`
- Admin: `https://yourapp.com/admin/blog`

Done.

## Routes

The package registers:

| Method | URL                              | Name                    | Description                       |
|--------|----------------------------------|-------------------------|-----------------------------------|
| GET    | `/blog`                          | `blog.index`            | Public listing                    |
| GET    | `/blog/category/{slug}`         | `blog.category`         | Posts in a category               |
| GET    | `/blog/{slug}`                   | `blog.show`             | Individual post                   |
| GET    | `/admin/blog`                    | `blog.admin.index`      | Admin list (filter/search)        |
| GET    | `/admin/blog/create`              | `blog.admin.create`     | Create form                       |
| GET    | `/admin/blog/{slug}/edit`      | `blog.admin.edit`       | Edit form                         |
| GET    | `/admin/blog/{slug}/preview`     | `blog.admin.preview`    | Preview a draft/scheduled post    |
| GET    | `/admin/blog/categories`         | `blog.admin.categories` | Categories CRUD                   |

The `/blog` and `/admin/blog` prefixes are configurable (`route_prefix`, `admin_route_prefix`).

## Configuration

See `config/blog.php` (published) — every key has comments explaining what it does, with examples. Summary:

- **`route_prefix`** / **`admin_route_prefix`** — where to mount the routes
- **`public_middleware`** / **`admin_middleware`** — middleware stacks
- **`author_model`** — User model
- **`layouts.public`** / **`layouts.admin`** — Blade layouts wrapping the content
- **`cta_view`** — optional view rendered at the end of each post (e.g. pricing, newsletter)
- **`markdown`** — options passed to `Str::markdown()`

## Customizing the layout

By default, the package uses its own neutral layouts. To use your app's layout:

```php
// config/blog.php
'layouts' => [
    'public' => 'layouts.app',
    'admin' => 'layouts.admin',
],
```

Custom layouts must have `@yield('content')` where the main content goes.

### Using a slot-based layout (Breeze, Jetstream, Folio)

If your app's layout is a Blade component (`<x-app-layout>`, `<x-site-layout>`, etc.), create a small wrapper view that bridges `@yield` and `{{ $slot }}`:

```blade
{{-- resources/views/layouts/blog-wrapper.blade.php --}}
<x-app-layout>
    @yield('content')
</x-app-layout>
```

Then point the config at the wrapper:

```php
'layouts' => [
    'public' => 'layouts.blog-wrapper',
    'admin' => 'layouts.blog-wrapper',
],
```

Three lines of plumbing, your app's chrome (header/nav/footer) wrapping the blog.

## Customizing the visual style

The package ships with `emerald` as the accent colour and `zinc` as the neutral. To brand it differently, publish the views and edit them directly:

```bash
php artisan vendor:publish --tag="simple-blog-views"
```

The Blade files land in `resources/views/vendor/blog/`. From that point Laravel uses your published copies, so a global find-and-replace (`emerald-` → `blue-`, `emerald-` → `rose-`, etc.) is enough to retheme the whole package.

Trade-off: published views are frozen at the version you published — bug fixes and new features that ship in later releases of the package won't reach them automatically. Diff your views against the upstream when upgrading.

## Injecting a CTA into posts

Create a view (e.g. `resources/views/components/blog-cta.blade.php`) and point to it:

```php
'cta_view' => 'components.blog-cta',
```

The view receives the `$post` variable and is rendered after the post content (on show) and below the feed (on index).

## Models

The package exposes:

- `Jessecruz\SimpleBlog\Models\Post`
- `Jessecruz\SimpleBlog\Models\PostCategory`

Use them directly when needed (e.g. to generate a sitemap, export content):

```php
use Jessecruz\SimpleBlog\Models\Post;

$posts = Post::published()->with('category')->latest('published_at')->get();
```

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## License

MIT — see [License File](LICENSE.md).
