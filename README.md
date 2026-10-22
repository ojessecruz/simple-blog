# Simple Blog

A ready-to-use Laravel blog — minimal reading-focused public listing, admin CRUD with Livewire, Markdown with HTML escaping, and draft preview in a new tab. Zero opinions on authentication: you plug it in via Laravel middleware (Gate, guard, or any combo).

## Requirements

- PHP 8.3+
- Laravel 11 / 12 / 13
- Livewire 3.8.3+ or 4.3.4+
- Tailwind CSS in the consuming app (the package ships Tailwind classes, it does not compile its own CSS)

## Installation

```bash
composer require ojessecruz/simple-blog
```

### Quick install (recommended)

One command publishes the config & migrations and wires Tailwind up for you:

```bash
php artisan simple-blog:install
```

It is idempotent — safe to re-run — and will:

- publish `config/blog.php` and the migrations;
- add the Tailwind source `@import` to `resources/css/app.css` (Tailwind v4), right after your `@import "tailwindcss";`;
- offer to run the migrations.

Afterwards, protect the admin routes (see below) and run `npm run build`. To wire things up by hand instead, follow the manual steps.

### Manual installation

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

Make Tailwind aware of the package views, otherwise it won't compile the classes they use and the blog renders unstyled.

**Tailwind v4** (CSS-based config) — import the package's CSS entrypoint from your app's main stylesheet. It registers the package views as a source, and because the path is resolved relative to the package file, your app never has to know the package's internal structure:

```css
/* resources/css/app.css */
@import 'tailwindcss';
@import '../../vendor/ojessecruz/simple-blog/resources/css/simple-blog.css';
```

**Tailwind v3** (`tailwind.config.js`) — add the views to the `content` array:

```js
content: [
    // ... your existing paths ...
    './vendor/ojessecruz/simple-blog/resources/views/**/*.blade.php',
],
```

If you published the views (`--tag="simple-blog-views"`), they live in `resources/views/` and are already covered by your app's default Tailwind sources — but keep the import above if you rely on any **non-published** views (e.g. the admin panel).

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

    public function getBlogAuthorAvatarUrl(): ?string
    {
        return $this->avatar_url; // or null if you don't have avatars
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
| GET    | `/blog`                          | `blog.index`            | Public listing (`?page=2`, …)     |
| GET    | `/blog/category/{slug}`         | `blog.category`         | Posts in a category (`?page=2`, …) |
| GET    | `/blog/feed`                     | `blog.feed`             | RSS feed of the latest posts      |
| GET    | `/blog/sitemap.xml`              | `blog.sitemap`          | XML sitemap of the blog           |
| GET    | `/blog/{slug}`                   | `blog.show`             | Individual post                   |
| GET    | `/admin/blog`                    | `blog.admin.index`      | Admin list (filter/search)        |
| GET    | `/admin/blog/create`              | `blog.admin.create`     | Create form                       |
| GET    | `/admin/blog/{slug}/edit`      | `blog.admin.edit`       | Edit form                         |
| GET    | `/admin/blog/{slug}/preview`     | `blog.admin.preview`    | Preview a draft/scheduled post    |
| GET    | `/admin/blog/categories`         | `blog.admin.categories` | Categories CRUD                   |

The `/blog` and `/admin/blog` prefixes are configurable (`route_prefix`, `admin_route_prefix`).

When the [JSON API](#json-api) is enabled, it also registers the `blog.api.*` routes under `/api/blog`.

## Configuration

See `config/blog.php` (published) — every key has comments explaining what it does, with examples. Summary:

- **`route_prefix`** / **`admin_route_prefix`** — where to mount the routes
- **`public_middleware`** / **`admin_middleware`** — middleware stacks
- **`api`** — opt-in JSON API: enable flag, prefix, middleware, token and page size (see [JSON API](#json-api))
- **`per_page`** — page size of the public index and category listings (default 12)
- **`author_model`** — User model
- **`layouts.public`** / **`layouts.admin`** — Blade layouts wrapping the content
- **`cta_view`** — optional view rendered at the end of each post (e.g. pricing, newsletter)
- **`mid_cta_view`** — optional view inserted once inside the post body (see [Injecting a CTA into posts](#injecting-a-cta-into-posts))
- **`share`** — which share buttons appear on posts, in what order, and the X handle to credit (see [Share buttons](#share-buttons))
- **`toc`** — table of contents above posts (on/off, minimum headings, depth) and "#" heading links (see [Table of contents and heading links](#table-of-contents-and-heading-links))
- **`feed`** / **`sitemap`** — RSS feed (on/off, item count, full content) and XML sitemap (on/off) (see [RSS feed and sitemap](#rss-feed-and-sitemap))
- **`publishing`** — schedule of the command that fires `PostPublished`, announce window, webhook URLs and signing secret (see [Publishing events and webhooks](#publishing-events-and-webhooks))
- **`public_back_url`** / **`admin_back_url`** — where the "back" links point (`null` hides them)
- **`seo`** — site/blog names, default description and images used in titles, meta tags and the Article JSON-LD (see [SEO](#seo))
- **`timezone`** — timezone editors type publish dates in and readers see them in; the database stays in `app.timezone` (see [Dates and timezones](#dates-and-timezones))
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

    @stack('head') {{-- Required: the package pushes <title>, meta, OG tags and JSON-LD here --}}

    <link rel="canonical" href="{{ \Jessecruz\SimpleBlog\Support\Seo::canonicalUrl() }}"> {{-- keeps ?page=N --}}

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

### Required directives in your layout

The package emits the `<title>`, meta description, OG tags and JSON-LD of every public page via `@push('head')`. For these to render, your layout's `<head>` must contain:

```blade
@stack('head')
```

Without it, titles and meta tags won't appear in the head. Do **not** add your own `<title>` to the public layout — the page already pushes one, and you would end up with two.

## SEO

Every public page renders exactly one `<title>`, a meta description when one is available, Open Graph / Twitter tags and, on published posts, a schema.org `Article`. The defaults work with zero configuration and derive from `config('app.name')`:

| Page | `<title>` | Description |
|---|---|---|
| Index | `Blog \| {app.name}` | `seo.description` |
| Category | `{Category} \| Blog \| {app.name}` | category description, then `seo.description` |
| Post | `{meta_title or title} \| Blog \| {app.name}` | `meta_description`, then `excerpt` |

Tune them in `config/blog.php`:

```php
'seo' => [
    'site_name' => 'Hoot',                                              // brand; null → config('app.name')
    'blog_name' => "Hoot's Blog",                                       // index title + suffix of the other pages
    'index_title' => "Hoot's Blog | Product feedback and roadmaps",    // optional tagline for the index only
    'description' => 'Guides on collecting feedback and shipping it.', // index subtitle + meta description
    'image' => 'https://usehoot.app/img/og-card.png',                  // default og:image
    'publisher_logo' => 'https://usehoot.app/img/logo.png',            // Article publisher logo
],
```

The index description is also shown as the subtitle under the index heading (a category shows its own description); without one, no subtitle is rendered.

Paginated listings canonicalize to themselves (`/blog?page=2`), and other query strings are dropped from the canonical URL; pages past the last one return 404. Admin previews of drafts and scheduled posts send `X-Robots-Tag: noindex, nofollow` and a matching meta tag.

Posts use `og_image`, then `cover_image`, then `seo.image` for their social image. The Article JSON-LD includes `headline`, `description`, `author` (from your `Author` model), `publisher`, `image`, `datePublished`, `dateModified` and `inLanguage`.

If you publish and customize the public views, `Jessecruz\SimpleBlog\Support\Seo` exposes the same resolvers (`indexTitle()`, `categoryTitle($category)`, `postTitle($post)`, `postImage($post)`, `articleSchema($post)`, `canonicalUrl()`, …) so your templates keep the fallbacks.

## Customizing the visual style

The package ships with `emerald` as the accent colour and `zinc` as the neutral. To brand it differently, publish the views and edit them directly. Three granularities are available:

```bash
# Everything (public + admin + layouts + icons)
php artisan vendor:publish --tag="simple-blog-views"

# Just the public side (index, show, share and toc components, feed, sitemap, public layout)
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

### Mid-article CTA

To also show a CTA inside the post body, point `mid_cta_view` at another view (it can be the same one):

```php
'mid_cta_view' => 'components.blog-mid-cta',
```

It is inserted once per post, only between top-level blocks (never inside a list, table, quote or code block):

- posts with two or more `## ` headings get it right before the second heading, i.e. after the first section;
- otherwise it goes after the block that ends nearest ~40% of the content;
- short posts (fewer than 4 top-level blocks) don't get it, so it never sits at the very top or bottom.

The view receives `$post` and is wrapped in `<div class="my-10" data-blog-mid-cta>`. It is independent from `cta_view`: set either, both or neither.

## Share buttons

Published posts show a row of share buttons in their footer. Pick the networks and their order in `config/blog.php`:

```php
'share' => [
    'networks' => ['whatsapp', 'x', 'linkedin', 'facebook', 'copy', 'native'], // default
    'x_via' => 'usehoot', // optional: "via @usehoot" on X
],
```

| Network | What it does |
|---|---|
| `whatsapp`, `x`, `linkedin`, `facebook`, `telegram` | Opens the network's share page in a new tab, pre-filled with the post URL (and title where supported) |
| `email` | `mailto:` link with the title as subject and the excerpt + URL as body |
| `copy` | Copies the post URL to the clipboard and confirms with a check icon and a screen-reader announcement |
| `native` | Opens the device's share sheet ([Web Share API](https://developer.mozilla.org/docs/Web/API/Navigator/share)), which lists every app installed (Instagram, Telegram, Messages…). Hidden in browsers without support, such as desktop Firefox |

- An empty `networks` array hides the share block; unknown entries are ignored.
- Every button has a translated `aria-label`. The small inline script behind `copy` and `native` is emitted once per page and carries `Vite::cspNonce()` when your app sets a CSP nonce.
- Shares are not rendered on the admin preview of drafts or scheduled posts, which have no public URL yet.
- Link previews on the networks come from the post's Open Graph tags (see [SEO](#seo)).

The block is a component, so a published view can place it anywhere (e.g. also below the title):

```blade
<x-blog::share :post="$post" />
```

To change its markup, publish the public views (`--tag="simple-blog-views-public"`) and edit `resources/views/vendor/blog/components/share.blade.php`. `Jessecruz\SimpleBlog\Support\Share` exposes the resolvers it uses (`networks()`, `links($post)`, `url($network, $post)`, `label($network)`) so custom templates keep the config behavior.

## Table of contents and heading links

Every heading in a post body gets an id built from its text, so any section can be linked to directly: `## Como começar` becomes `/blog/my-post#como-comecar` (repeated headings get `-2`, `-3`, …; headings with nothing to slug, such as only emoji, fall back to `section`). Hovering a heading shows a `#` link to copy that address; on touch screens it stays faintly visible.

Posts with at least three headings also get a collapsible "In this article" table of contents above the body. Tune both in `config/blog.php`:

```php
'toc' => [
    'enabled' => true,     // table of contents above the body
    'min_headings' => 3,   // fewest listed headings for the table to show
    'depth' => 2,          // 1 → sections only (usually ##), 2 → also subsections (###)
    'permalinks' => true,  // "#" link after each heading
],
```

- Levels are counted from the post's top heading level, so a body written with `#` or starting at `###` gets the same table.
- Heading text stays escaped like the rest of the body; the table repeats the plain text (no inline HTML).
- The RSS feed's `content:encoded` and `Post::renderedBody()` keep the plain headings, without ids or `#` links.

The table is a component, so a published view can place it anywhere. Render the body and the table from the same call, so the links match the ids:

```blade
@php([$html, $toc] = \Jessecruz\SimpleBlog\Support\TableOfContents::render($post))

<x-blog::toc :items="$toc" />
<div class="blog-content">{!! $html !!}</div>
```

## RSS feed and sitemap

Both are on by default and live under the blog prefix.

**Feed** — `/blog/feed` is an RSS 2.0 feed of the latest published posts: title, link, publication date, author, category, excerpt and cover image (`media:content`). Every public page advertises it with `<link rel="alternate" type="application/rss+xml">`, so browsers and readers find it from the blog URL. It also works with RSS-to-email tools (Mailchimp, Kit, …) to send new posts to a newsletter.

```php
'feed' => [
    'enabled' => true,
    'limit' => 20,            // latest posts listed
    'full_content' => false,  // true: also send the rendered body (<content:encoded>)
],
```

**Sitemap** — `/blog/sitemap.xml` lists the index, every category with published posts and every published post, each with its last modification. Drafts and scheduled posts stay out until they go live. Point search engines at it from your `robots.txt`, or submit it in Google Search Console:

```text
Sitemap: https://example.com/blog/sitemap.xml
```

If your app already builds its own sitemap, set `'sitemap' => ['enabled' => false]` and add `route('blog.sitemap')` to your sitemap index instead, or keep both.

`feed` is a reserved post slug: the admin and the API reject it, since `/blog/feed` would never reach the post.

## Publishing events and webhooks

When a post goes live, the package dispatches `Jessecruz\SimpleBlog\Events\PostPublished` **once**. That covers posts published right away and scheduled posts reaching their date. Use it to post on social networks, send a newsletter or purge a cache:

```php
use Illuminate\Support\Facades\Event;
use Jessecruz\SimpleBlog\Events\PostPublished;

Event::listen(function (PostPublished $event) {
    $post = $event->post; // category and author loaded; $post->url() is the public URL
});
```

The `simple-blog:announce` command finds posts that went live and fires the event. The package schedules it every minute (`publishing.schedule`), so **your app's scheduler must be running**: `php artisan schedule:work` locally, or the usual `* * * * * php artisan schedule:run` cron. A post is announced at most once. If a listener throws, the error is reported and the other posts still go out.

Posts that went live more than `publishing.announce_window` hours ago (default 24) are marked without firing the event. That stops a bulk import of old posts or a scheduler that was down for days from flooding your channels.

**Webhooks.** For no-code tools (Zapier, Make, n8n), list URLs and every announcement is POSTed to each of them through your queue (3 attempts):

```dotenv
BLOG_WEBHOOK_URLS=https://hooks.zapier.com/hooks/catch/123/abc/,https://n8n.example.com/webhook/blog
BLOG_WEBHOOK_SECRET=a-long-random-secret
```

```json
{
    "event": "post.published",
    "post": { "id": 12, "slug": "how-to-reduce-no-shows", "title": "…", "url": "https://example.com/blog/how-to-reduce-no-shows", "status": "published", "…": "same fields as the JSON API" }
}
```

Requests carry `X-Simple-Blog-Event: post.published` and, with a secret, `X-Simple-Blog-Signature: sha256=<HMAC-SHA256 of the raw body>`. Verify it on the receiving side:

```php
$expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

abort_unless(hash_equals($expected, (string) $request->header('X-Simple-Blog-Signature')), 401);
```

**Upgrading from 0.8.x**: this adds the `blog_posts.announced_at` column. Publish and run the new migration; posts already live are marked as announced, so nothing is re-sent:

```bash
php artisan vendor:publish --tag=simple-blog-migrations
php artisan migrate
```

## Dates and timezones

Publish dates are stored in `app.timezone`, like every Laravel date. Many apps run in UTC (the Laravel default) while their editors and readers live elsewhere; set `timezone` so the blog works in local time:

```php
'timezone' => 'America/Sao_Paulo', // null → app.timezone
```

- The admin "Publish at" field is typed and shown in this timezone, which is displayed under the field. "10:00" means 10:00 in São Paulo, stored as 13:00 UTC.
- Public post dates, the scheduled-post preview notice and the admin list are shown in this timezone, so a post published at 23:30 local time doesn't show the next day's date.
- Machine-readable dates (`<time datetime>`, Open Graph, JSON-LD, the JSON API) carry their offset and point to the same instant either way.
- Dates assigned to `Post::$published_at` with an offset (an ISO 8601 string, a Carbon in another timezone) are converted to `app.timezone` before saving.

Date formats come from the `date_format`, `datetime_format` and `datetime_short_format` lang strings. In custom templates, `Jessecruz\SimpleBlog\Support\Dates::format($post->published_at, $format)` converts to the blog timezone and translates month names.

## JSON API

An optional REST API to manage content from automations (n8n, CI jobs, AI agents). It is **disabled by default** — no routes are registered until you turn it on:

```dotenv
BLOG_API_ENABLED=true
BLOG_API_TOKEN=a-long-random-secret
```

Every request must send `Authorization: Bearer {BLOG_API_TOKEN}` and `Accept: application/json`. While the token is empty, every request is rejected with `401`.

| Method | URL                          | Name                         | Description                               |
|--------|------------------------------|------------------------------|-------------------------------------------|
| GET    | `/api/blog/categories`       | `blog.api.categories.index`  | List categories                           |
| POST   | `/api/blog/categories`       | `blog.api.categories.store`  | Create a category (`name`, `slug`?, `description`?) |
| GET    | `/api/blog/posts`            | `blog.api.posts.index`       | Paginated posts, newest first, without `body` |
| POST   | `/api/blog/posts`            | `blog.api.posts.store`       | Create a post                             |
| GET    | `/api/blog/posts/{slug}`     | `blog.api.posts.show`        | A single post, with `body`                |
| PATCH  | `/api/blog/posts/{slug}`     | `blog.api.posts.update`      | Update only the fields sent               |

Post payload:

```json
{
    "title": "How to reduce no-shows",
    "excerpt": "Why clients miss appointments and what to do about it.",
    "body": "## Markdown body...",
    "category": "scheduling",
    "published_at": "2026-10-01T09:00:00-03:00",
    "keywords": ["no-shows", "reminders"]
}
```

- `title`, `excerpt`, `body` and `category` (a category **slug**) are required on create and optional on update.
- `slug` defaults to `Str::slug(title)` on create; `reading_time` is estimated from the body (200 words/minute) when omitted. `feed` is a reserved slug.
- `published_at`: omitted or `null` → draft; in the past → published; in the future → scheduled. The response reports it as `status`. An offset is honored (`2026-10-01T09:00:00-03:00` and `2026-10-01T12:00:00Z` are the same instant); a value without one is read in `app.timezone`. Responses use `app.timezone`.
- Also accepted: `cover_image`, `og_image`, `meta_title`, `meta_description` and `author_id` (validated against `author_model` when one is configured).

To use your own auth instead of the shared token, replace the middleware stack:

```php
'api' => [
    // ...
    'middleware' => ['api', 'auth:sanctum', 'can:manage-blog'],
],
```

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

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening an issue or a pull request. Security problems are reported privately: see [SECURITY.md](SECURITY.md).

## License

MIT — see [License File](LICENSE.md).
