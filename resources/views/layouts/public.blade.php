<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Each public view pushes its own <title>, description, OG tags and JSON-LD --}}
    @stack('head')

    <link rel="canonical" href="{{ request()->url() }}">
    <meta name="robots" content="index, follow">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ \Jessecruz\SimpleBlog\Support\Seo::siteName() }}">
    <meta property="og:locale" content="{{ app()->getLocale() }}">

    @if($assets = config('blog.assets'))
        @vite($assets)
    @endif
</head>
<body class="antialiased font-sans bg-white dark:bg-zinc-950 min-h-screen text-zinc-900 dark:text-zinc-100">
    @yield('header')

    <main>
        @yield('content')
    </main>

    @yield('footer')
</body>
</html>
