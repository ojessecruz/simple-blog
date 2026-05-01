<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Blog')</title>

    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ request()->url() }}">
    <meta name="robots" content="index, follow">

    @if($assets = config('blog.assets'))
        @vite($assets)
    @endif

    @stack('head')
</head>
<body class="antialiased font-sans bg-white dark:bg-zinc-950 min-h-screen text-zinc-900 dark:text-zinc-100">
    @yield('header')

    <main>
        @yield('content')
    </main>

    @yield('footer')
</body>
</html>
