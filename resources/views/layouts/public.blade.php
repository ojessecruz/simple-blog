<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @if(isset($post) && $post)
        <title>{{ $post->meta_title ?? $post->title }}</title>
        <meta name="description" content="{{ $post->meta_description ?? $post->excerpt }}">
        <meta name="keywords" content="{{ implode(', ', $post->keywords ?? []) }}">
        <meta property="og:title" content="{{ $post->title }}">
        <meta property="og:description" content="{{ $post->excerpt }}">
        <meta property="og:type" content="article">
        @if($post->og_image)
            <meta property="og:image" content="{{ $post->og_image }}">
        @endif
        @if($post->published_at)
            <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
        @endif
        @if($post->isPublished())
            <script type="application/ld+json">
            {
                "@@context": "https://schema.org",
                "@@type": "Article",
                "headline": "{{ $post->title }}",
                "description": "{{ $post->excerpt }}",
                "datePublished": "{{ $post->published_at->toIso8601String() }}",
                "mainEntityOfPage": {"@type": "WebPage", "@id": "{{ $post->url() }}"}
            }
            </script>
        @endif
    @else
        <title>@yield('title', 'Blog')</title>
    @endif

    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ request()->url() }}">
    <meta name="robots" content="index, follow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Serif+Pro:wght@400;600;700&display=swap">

    <style>
        .blog-serif { font-family: "Source Serif Pro", ui-serif, Georgia, Cambria, "Times New Roman", Times, serif; }
    </style>

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
