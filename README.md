# Simple Blog

A ready-to-use Laravel blog — minimal reading-focused public listing, admin CRUD with Livewire, Markdown with HTML escaping, and draft preview in a new tab. Zero opinions on authentication: you plug it in via Laravel middleware (Gate, guard, or any combo).

## Requirements

- PHP 8.3+
- Laravel 11 / 12 / 13
- Livewire 3.5+ or 4
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

The package ships two neutral default layouts, both pulled from `config/blog.php`:

- **public** (`blog::layouts.public`) — used for `/blog` and `/blog/{slug}`. Plain HTML, no nav, content rendered through `@yield('content')`.
- **admin** (`blog::layouts.admin`) — used for `/admin/blog`. Slot-based, content rendered through `{{ $slot }}` so it can be pointed straight at modern Laravel layouts.

Most apps want the public side wrapped in their site chrome (header, nav, footer, SEO meta) and the admin inside their auth shell.

### Public layout: build it from scratch

The simplest path is creating a dedicated layout for the blog with `@yield('content')`:

```blade
{{-- resources/views/layouts/blog-public.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>

    @stack('head') {{-- Required: lets the package emit SEO meta and JSON-LD --}}

    @vite('resources/css/app.css')
</head>
<body class="bg-white text-gray-900">
    <header class="border-b">
        <div class="max-w-3xl mx-auto px-4 py-4">
            <a href="/" class="text-xl font-bold">{{ config('app.name') }}</a>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="border-t mt-16 py-8 text-center text-sm text-gray-500">
        © {{ date('Y') }} {{ config('app.name') }}
    </footer>
</body>
</html>
```

Then point the config at it:

```php
'layouts' => [
    'public' => 'layouts.blog-public',
    'admin' => 'blog::layouts.admin',
],
```

If your layout already loads its own CSS via `@vite`, set `'assets' => []` in `config/blog.php` to avoid loading it twice.

### Public layout: reuse a Blade component (slot-based)

If your app already has a slot-based layout (Breeze, Jetstream, Folio, or a custom `<x-site-layout>`), create a small wrapper that bridges `@yield('content')` and `{{ $slot }}`:

```blade
{{-- resources/views/layouts/blog-public.blade.php --}}
<x-site-layout>
    @yield('content')
</x-site-layout>
```

```php
'public' => 'layouts.blog-public',
```

Three lines of plumbing, your app's chrome wrapping the blog.

### Admin layout

The admin Livewire components use `->layout()` (slot-based), so the admin layout key can point at any slot-based view directly:

```php
'admin' => 'layouts.app',  // your <x-app-layout> view, no wrapper needed
```

If your admin layout is `@yield`-based instead, build a small slot bridge:

```blade
{{-- resources/views/layouts/blog-admin.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
```

### Make Tailwind aware of the package views

If you use Tailwind CSS, add the package views to your `tailwind.config.js` `content` array so JIT compiles classes used in the package:

```js
content: [
    // ... your existing paths ...
    './vendor/ojessecruz/simple-blog/resources/views/**/*.blade.php',
],
```

### Required directives in your layout

The package emits SEO meta tags, OG tags and JSON-LD via `@push('head')`. For these to render, your layout's `<head>` must contain:

```blade
@stack('head')
```

Without it, `<title>` and meta tags from posts won't appear in the head.

## Customizing the visual style

The package ships with `emerald` as the accent colour and `zinc` as the neutral. To brand it differently, publish the views and edit them directly. Three granularities are available:

```bash
# Everything (public + admin + layouts + icons)
php artisan vendor:publish --tag="simple-blog-views"

# Just the public side (index, show, public layout)
php artisan vendor:publish --tag="simple-blog-views-public"

# Just the admin side (Livewire admin views + admin layout)
php artisan vendor:publish --tag="simple-blog-views-admin"

# Just the layouts (public and admin shells, no inner content)
php artisan vendor:publish --tag="simple-blog-views-layouts"
```

The Blade files land in `resources/views/vendor/blog/`. From that point Laravel uses your published copies, so a global find-and-replace (`emerald-` → `blue-`, `emerald-` → `rose-`, etc.) is enough to retheme that slice.

Trade-off: published views are frozen at the version you published — bug fixes and new features that ship in later releases of the package won't reach them automatically. Publishing only the slice you actually want to customize (admin or public) keeps the rest tracking upstream.

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
