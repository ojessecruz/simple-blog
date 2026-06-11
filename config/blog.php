<?php

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
