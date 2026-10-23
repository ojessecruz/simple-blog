<?php

use Jessecruz\SimpleBlog\Http\Middleware\ValidateApiToken;

return [
    /*
    |--------------------------------------------------------------------------
    | Public route prefix
    |--------------------------------------------------------------------------
    |
    | The URL segment where the public blog is mounted. With 'blog', routes
    | become /blog, /blog/{slug}, /blog/category/{slug}. Change to 'articles'
    | and everything moves to /articles automatically.
    |
    */
    'route_prefix' => 'blog',

    /*
    |--------------------------------------------------------------------------
    | Admin route prefix
    |--------------------------------------------------------------------------
    |
    | Where the post and category CRUD lives. Default: /admin/blog. Use any
    | path you like — just don't conflict with existing app routes.
    |
    */
    'admin_route_prefix' => 'admin/blog',

    /*
    |--------------------------------------------------------------------------
    | Public middleware
    |--------------------------------------------------------------------------
    |
    | Stack applied to /index, /show and /category. Usually 'web' alone is
    | enough (session + CSRF). Add extra middleware here if you need feature
    | flag gating, response caching, etc.
    |
    */
    'public_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Admin middleware
    |--------------------------------------------------------------------------
    |
    | This is where you protect the blog admin. The package embeds NO
    | authorization logic — you plug it in via Laravel middleware.
    |
    | Examples:
    |
    |   Specific Gate:
    |     ['web', 'auth', 'can:manage-blog']
    |
    |   Separate guard:
    |     ['web', 'auth:admin']
    |
    |   App-specific middleware combo:
    |     ['web', 'auth', 'verified', 'super.admin']
    |
    | If the stack authorizes the user, the app can list/create/edit posts.
    | If it blocks, the request is rejected before reaching Livewire.
    |
    */
    'admin_middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | JSON API
    |--------------------------------------------------------------------------
    |
    | Optional REST API to list/create categories and list/show/create/update
    | posts — handy for automations (n8n, CI, AI agents) that publish content.
    | Disabled by default: no API routes are registered until you enable it.
    |
    |   - enabled:    Registers the routes. BLOG_API_ENABLED=true in .env.
    |   - prefix:     URL prefix. Default /api/blog/posts, /api/blog/categories.
    |   - middleware: Stack applied to every API route. The default
    |                 ValidateApiToken requires `Authorization: Bearer {token}`
    |                 and rejects everything while `token` is empty. Swap it
    |                 for your own auth, e.g. ['api', 'auth:sanctum',
    |                 'can:manage-blog'].
    |   - token:      Shared secret checked by ValidateApiToken.
    |                 BLOG_API_TOKEN in .env.
    |   - per_page:   Page size of GET /posts.
    |
    | Example:
    |     'api' => [
    |         'enabled' => true,
    |         'prefix' => 'api/blog',
    |         'middleware' => ['api', \Jessecruz\SimpleBlog\Http\Middleware\ValidateApiToken::class],
    |         'token' => env('BLOG_API_TOKEN'),
    |         'per_page' => 50,
    |     ],
    |
    */
    'api' => [
        'enabled' => env('BLOG_API_ENABLED', false),
        'prefix' => 'api/blog',
        'middleware' => ['api', ValidateApiToken::class],
        'token' => env('BLOG_API_TOKEN'),
        'per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Posts per page
    |--------------------------------------------------------------------------
    |
    | Page size of the public index and category listings (`?page=2`, …).
    | Multiples of 6 fill the 2- and 3-column grid evenly. Pages past the
    | last one return 404.
    |
    | Example:
    |     'per_page' => 18,
    |
    */
    'per_page' => 12,

    /*
    |--------------------------------------------------------------------------
    | Author model
    |--------------------------------------------------------------------------
    |
    | The model class used by the Post::author() relationship — usually your
    | app's User. The model must implement
    | Jessecruz\SimpleBlog\Contracts\Author so the package can render the
    | author's name and initials in the layout.
    |
    | Example:
    |     App\Models\User::class
    |
    */
    'author_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Layouts
    |--------------------------------------------------------------------------
    |
    | The package ships neutral defaults (blog::layouts.public for the public
    | side using @yield('content'), blog::layouts.admin for admin using
    | {{ \$slot }}). Point at your own views to wrap the blog in your app's
    | chrome — see the README for slot-based hosts (Breeze/Jetstream/Folio)
    | and a full example of writing a layout from scratch.
    |
    */
    'layouts' => [
        'public' => 'blog::layouts.public',
        'admin' => 'blog::layouts.admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | CTA view (Call to Action)
    |--------------------------------------------------------------------------
    |
    | Optional view rendered at the end of every post (after the content,
    | before "Continue reading"). Use it to inject pricing, newsletter
    | signups, product banners, etc.
    |
    | Leave `null` to render nothing. The view receives the $post variable.
    |
    | Example:
    |     'cta_view' => 'components.blog-pricing-cta'
    |
    */
    'cta_view' => null,

    /*
    |--------------------------------------------------------------------------
    | Mid-article CTA view
    |--------------------------------------------------------------------------
    |
    | Optional view inserted ONCE inside the post body, between two top-level
    | blocks (never inside a list, table, quote or code block). Placement:
    |
    |   - 2+ <h2> headings: before the second one (after the first section)
    |   - otherwise: after the block that ends nearest ~40% of the content
    |
    | Short posts (fewer than 4 top-level blocks) are skipped, so it never sits
    | at the very top or very bottom. Independent from `cta_view`.
    |
    | Leave `null` to render nothing. The view receives the $post variable.
    |
    | Example:
    |     'mid_cta_view' => 'components.blog-mid-cta'
    |
    */
    'mid_cta_view' => null,

    /*
    |--------------------------------------------------------------------------
    | Share buttons
    |--------------------------------------------------------------------------
    |
    | Share buttons rendered in the footer of every published post (never on
    | admin previews of drafts or scheduled posts).
    |
    |   - networks: Buttons to show, in this order. An empty array hides the
    |               share block entirely. Available:
    |                 'whatsapp', 'x', 'linkedin', 'facebook', 'telegram',
    |                 'email'  → share links (mailto: for email)
    |                 'copy'   → copies the post URL to the clipboard
    |                 'native' → opens the device's share sheet (Web Share
    |                            API); only shown in browsers that support
    |                            it: mobile browsers and Safari/Chrome/Edge
    |                            on desktop (not desktop Firefox)
    |               Unknown entries are ignored.
    |   - x_via:    X handle credited in the post as "via @handle" (with or
    |               without the "@"). null → no credit.
    |
    | The block is also available as a component you can place anywhere in
    | a published view: <x-blog::share :post="$post" />
    |
    | Example:
    |     'share' => [
    |         'networks' => ['native', 'whatsapp', 'telegram', 'email', 'copy'],
    |         'x_via' => 'usehoot',
    |     ],
    |
    */
    'share' => [
        'networks' => ['whatsapp', 'x', 'linkedin', 'facebook', 'copy', 'native'],
        'x_via' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table of contents and heading links
    |--------------------------------------------------------------------------
    |
    | Every heading of a post body gets an id built from its text, so any
    | section can be linked to: "## Como começar" → /blog/my-post#como-comecar
    | (repeated headings get -2, -3, …).
    |
    |   - enabled:      Show a table of contents above the post body.
    |   - min_headings: Fewest listed headings for the table to show; shorter
    |                   posts don't get one.
    |   - depth:        Heading levels listed, counted from the post's top
    |                   level: 1 → only the sections (usually ##), 2 → also
    |                   their subsections (###), and so on.
    |   - permalinks:   "#" link after each heading (shown on hover) to copy
    |                   a link to that section.
    |
    | The table is also available as a component for published views:
    |     @php([$html, $toc] = \Jessecruz\SimpleBlog\Support\TableOfContents::render($post))
    |     <x-blog::toc :items="$toc" />
    |
    | Example:
    |     'toc' => [
    |         'enabled' => true,
    |         'min_headings' => 4,
    |         'depth' => 1,
    |         'permalinks' => false,
    |     ],
    |
    */
    'toc' => [
        'enabled' => true,
        'min_headings' => 3,
        'depth' => 2,
        'permalinks' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | RSS feed
    |--------------------------------------------------------------------------
    |
    | RSS 2.0 feed of the latest published posts at /{route_prefix}/feed
    | (route `blog.feed`), advertised to browsers and readers by a
    | <link rel="alternate"> on every public page. Works with feed readers
    | and RSS-to-email tools (Mailchimp, Kit, …).
    |
    |   - enabled:      Registers the route. "feed" is a reserved post slug
    |                   either way.
    |   - limit:        How many of the latest posts the feed lists.
    |   - full_content: Also send each post's rendered body
    |                   (<content:encoded>), not only the excerpt. Useful
    |                   for RSS-to-email; readers then don't need to visit.
    |
    | Example:
    |     'feed' => [
    |         'enabled' => true,
    |         'limit' => 50,
    |         'full_content' => true,
    |     ],
    |
    */
    'feed' => [
        'enabled' => true,
        'limit' => 20,
        'full_content' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | XML sitemap
    |--------------------------------------------------------------------------
    |
    | Sitemap at /{route_prefix}/sitemap.xml (route `blog.sitemap`) listing
    | the index, every category with published posts and every published
    | post, each with its last modification date. Submit it in Google Search
    | Console or reference it from your robots.txt:
    |
    |     Sitemap: https://example.com/blog/sitemap.xml
    |
    | Example:
    |     'sitemap' => ['enabled' => false],   // you build your own sitemap
    |
    */
    'sitemap' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | View counting
    |--------------------------------------------------------------------------
    |
    | Each read of a public post adds to its `views_count`, shown in the
    | admin post list. Only GET requests count — admin previews never do —
    | and counting never changes the post's last-modified date.
    |
    |   - enabled:          Count views at all. false → views_count stays as
    |                       it is (e.g. you rely on your own analytics).
    |   - ignore_bots:      Skip requests whose User-Agent is empty or names
    |                       a crawler, link preview, AI agent or script
    |                       (Googlebot, WhatsApp/Slack previews, GPTBot,
    |                       curl, …). Bots posing as browsers still count.
    |   - once_per_session: Count each post once per visitor session, so
    |                       reloads don't add up. Needs a session (the
    |                       default `web` public middleware starts one);
    |                       without it every request counts.
    |
    | Example:
    |     'views' => [
    |         'enabled' => true,
    |         'ignore_bots' => true,
    |         'once_per_session' => false,   // count every page load
    |     ],
    |
    */
    'views' => [
        'enabled' => true,
        'ignore_bots' => true,
        'once_per_session' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image uploads
    |--------------------------------------------------------------------------
    |
    | The post form can upload the cover and social (og:image) images to a
    | filesystem disk, besides taking a URL. A post stores an upload as its
    | path on the disk and builds the URL when read, so changing the disk's
    | URL (APP_URL, a CDN) updates every image.
    |
    | Readers load the images straight from the disk's URL, so it must be
    | public: the `public` disk needs `php artisan storage:link`. Where local
    | files don't survive a deploy (containers, serverless), use S3, R2 or
    | similar; those disks need a `url` with the bucket's public domain,
    | otherwise URLs point at the API endpoint.
    |
    |   - enabled:   false → no uploads, images by URL only.
    |                BLOG_IMAGES_ENABLED in .env.
    |   - disk:      Disk from config/filesystems.php. null → the app's
    |                default disk (FILESYSTEM_DISK), except Laravel's private
    |                `local` disk, replaced by `public`. Set it when your
    |                default disk is private. A name that isn't configured
    |                there turns uploads off. BLOG_IMAGES_DISK in .env.
    |   - directory: Folder on the disk. An upload no post uses any more —
    |                replaced, removed or its post deleted — is deleted.
    |   - max_size:  Largest upload, in kilobytes. PHP's
    |                upload_max_filesize (2 MB by default) and Livewire's
    |                temporary upload limit (12 MB) also apply.
    |
    | Example:
    |     'images' => [
    |         'enabled' => true,
    |         'disk' => 'r2',            // not the default disk
    |         'directory' => 'blog/images',
    |         'max_size' => 5120,        // 5 MB
    |     ],
    |
    */
    'images' => [
        'enabled' => env('BLOG_IMAGES_ENABLED', true),
        'disk' => env('BLOG_IMAGES_DISK'),
        'directory' => 'blog',
        'max_size' => 2048,
    ],

    /*
    |--------------------------------------------------------------------------
    | Publishing events and webhooks
    |--------------------------------------------------------------------------
    |
    | When a post goes live — published right away or reaching its scheduled
    | date — the package dispatches Jessecruz\SimpleBlog\Events\PostPublished
    | once and POSTs it to the webhooks below. Listen to it to post on social
    | networks, send a newsletter, purge a cache, etc.
    |
    | The `simple-blog:announce` command detects posts that went live. The
    | package schedules it for you, so your app's scheduler must be running
    | (`php artisan schedule:work`, or the `schedule:run` cron).
    |
    |   - schedule:        Cron expression for the command. null → not
    |                      scheduled (run `simple-blog:announce` yourself).
    |   - announce_window: Hours. Posts that went live longer ago than this
    |                      (imports, backdated posts, a scheduler that was
    |                      down for days) are marked as announced without
    |                      firing the event. null → announce them all.
    |   - webhooks:        URLs that receive a JSON POST
    |                      ({"event": "post.published", "post": {...}}, the
    |                      same post shape as the JSON API) through your
    |                      queue, retried up to 3 times. BLOG_WEBHOOK_URLS in
    |                      .env, comma-separated.
    |   - webhook_secret:  Signs each body: header X-Simple-Blog-Signature is
    |                      "sha256=" + HMAC-SHA256(body, secret).
    |                      BLOG_WEBHOOK_SECRET in .env. null → unsigned.
    |
    | Example:
    |     'publishing' => [
    |         'schedule' => '0,15,30,45 * * * *',  // every 15 minutes
    |         'announce_window' => 48,
    |         'webhooks' => ['https://hooks.zapier.com/hooks/catch/123/abc/'],
    |         'webhook_secret' => env('BLOG_WEBHOOK_SECRET'),
    |     ],
    |
    */
    'publishing' => [
        'schedule' => '* * * * *',
        'announce_window' => 24,
        'webhooks' => array_values(array_filter(explode(',', (string) env('BLOG_WEBHOOK_URLS', '')))),
        'webhook_secret' => env('BLOG_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public "Back to site" URL
    |--------------------------------------------------------------------------
    |
    | Where the "Back to site" link at the top of the public blog index
    | should go. Defaults to the app root. Use any path or full URL.
    |
    | Leave `null` to hide the link entirely.
    |
    | Example:
    |     'public_back_url' => '/welcome',
    |
    */
    'public_back_url' => '/',

    /*
    |--------------------------------------------------------------------------
    | Admin "Back" URL
    |--------------------------------------------------------------------------
    |
    | Where the "Back" link at the top of /admin/blog should go. Typically
    | the host app's admin dashboard or wherever the operator was before
    | entering the blog area.
    |
    | Leave `null` to hide the back link entirely.
    |
    | Example:
    |     'admin_back_url' => '/admin',
    |
    */
    'admin_back_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Assets (Vite)
    |--------------------------------------------------------------------------
    |
    | Vite entrypoints injected into <head> in the package's default layouts.
    | Since the package ships Tailwind classes, it needs the consuming app's
    | compiled CSS to render correctly.
    |
    | Default: the standard Tailwind entrypoint of a Laravel app. Set to an
    | empty array (`[]`) if you pointed `layouts.public`/`layouts.admin` at
    | your own layout (which already loads its assets).
    |
    */
    'assets' => [
        'resources/css/app.css',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    |
    | Forces the locale used for the package's translated strings (admin and
    | public views). Leave `null` to follow the host app's locale —
    | `app()->getLocale()`. Set to a specific locale to pin the blog to that
    | language regardless of the rest of the app.
    |
    | Built-in locales: 'en', 'pt_BR', 'es'. Publish the lang files
    | (`vendor:publish --tag="simple-blog-lang"`) to add more.
    |
    | Examples:
    |     'locale' => null,        // follow app.locale
    |     'locale' => 'pt_BR',     // always Portuguese (Brazil)
    |     'locale' => 'es',        // always Spanish
    |
    */
    'locale' => null,

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | Timezone editors type publish dates in (admin form) and readers see them
    | in (public pages, admin list). The database keeps storing dates in
    | `app.timezone`; only input and display are converted.
    |
    | Leave `null` to use `app.timezone`. Set it when the app runs in UTC (the
    | Laravel default) but the blog's editors and audience live elsewhere —
    | otherwise a post scheduled for "10:00" goes out at 10:00 UTC and late
    | posts show the next day's date.
    |
    | Any PHP timezone identifier is accepted.
    |
    | Examples:
    |     'timezone' => null,                   // follow app.timezone
    |     'timezone' => 'America/Sao_Paulo',
    |     'timezone' => 'Europe/Lisbon',
    |
    */
    'timezone' => null,

    /*
    |--------------------------------------------------------------------------
    | SEO defaults for the public pages
    |--------------------------------------------------------------------------
    |
    | Every public page emits exactly one <title>, a meta description, Open
    | Graph tags and (on posts) a schema.org Article via @push('head'). These
    | keys feed the fallbacks; all of them are optional.
    |
    |   - site_name:       Brand name used in titles and as the Article
    |                      publisher. null → config('app.name').
    |   - blog_name:       The blog's own name. It is the index title and the
    |                      suffix of category and post titles
    |                      ("{Category} | {blog_name}", "{Post} | {blog_name}").
    |                      null → "Blog | {site_name}".
    |   - index_title:     Full <title> for the index only, when you want a
    |                      tagline there. null → blog_name.
    |   - description:     Meta description for the index and for categories
    |                      without their own description, also shown as the
    |                      subtitle under the index heading. null → no tag
    |                      and no subtitle.
    |   - image:           Absolute URL of the default og:image for the index,
    |                      categories and posts without a cover. null → no tag.
    |   - publisher_logo:  Absolute URL of the logo used in the Article
    |                      publisher. null → publisher without logo.
    |
    | Example:
    |     'seo' => [
    |         'site_name' => 'Hoot',
    |         'blog_name' => "Hoot's Blog",
    |         'index_title' => "Hoot's Blog | Product feedback, roadmaps and changelogs",
    |         'description' => 'Stories and guides on collecting feedback and shipping what users ask for.',
    |         'image' => 'https://usehoot.app/img/og-card.png',
    |         'publisher_logo' => 'https://usehoot.app/img/logo.png',
    |     ],
    |
    */
    'seo' => [
        'site_name' => null,
        'blog_name' => null,
        'index_title' => null,
        'description' => null,
        'image' => null,
        'publisher_logo' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown rendering options
    |--------------------------------------------------------------------------
    |
    | Passed straight to Str::markdown() (CommonMark). Defaults:
    |
    |   - html_input: 'escape' — raw HTML in markdown is rendered as literal
    |     text (XSS protection when authors are untrusted)
    |   - allow_unsafe_links: false — blocks javascript:, data:, etc.
    |
    | If ALL authors are internal/trusted and you want to allow inline HTML,
    | switch 'html_input' to 'allow'.
    |
    */
    'markdown' => [
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
    ],
];
