<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Blog Admin')</title>

    @stack('head')

    @livewireStyles
</head>
<body class="antialiased font-sans bg-zinc-50 dark:bg-zinc-950 min-h-screen text-zinc-900 dark:text-zinc-100">
    @yield('content')

    @livewireScriptConfig
</body>
</html>
