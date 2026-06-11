@extends(config('blog.layouts.public'))

@push('head')
    <title>@if(isset($currentCategory)){{ $currentCategory->name }} — @endif Blog</title>
    @if(isset($currentCategory) && $currentCategory->description)
        <meta name="description" content="{{ $currentCategory->description }}">
    @endif
    <meta property="og:title" content="@if(isset($currentCategory)){{ $currentCategory->name }} — @endif Blog">
    <meta property="og:type" content="website">
@endpush

@section('content')
    <header class="relative overflow-hidden border-b border-zinc-200 dark:border-zinc-800">
        <div class="pointer-events-none absolute inset-0 text-zinc-900 opacity-[0.05] dark:text-zinc-100"
             style="background-image: radial-gradient(currentColor 1px, transparent 1px); background-size: 26px 26px;"
             aria-hidden="true"></div>

        <div class="relative mx-auto max-w-2xl px-6 pb-12 pt-10 sm:pb-14 sm:pt-12">
            @if(isset($currentCategory))
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">
                    {{ __('blog::messages.category') }}
                </p>
            @endif

            <h1 class="mt-4 text-5xl font-bold leading-[1.05] tracking-tight text-zinc-900 sm:text-6xl dark:text-zinc-100">
                <span class="relative inline-block font-serif italic">
                    {{ isset($currentCategory) ? $currentCategory->name : __('blog::messages.blog') }}
                </span>
            </h1>

            <p class="mt-7 text-base leading-relaxed text-zinc-600 sm:text-lg dark:text-zinc-400">
                @if(isset($currentCategory) && $currentCategory->description)
                    {{ $currentCategory->description }}
                @else
                    {{ __('blog::messages.latest_posts') }}
                @endif
            </p>
        </div>
    </header>

    <div class="mx-auto max-w-2xl px-6 py-10">

        @if($categories->isNotEmpty())
            <nav class="mb-10 flex flex-wrap gap-2">
                <a href="{{ route('blog.index') }}"
                   @class([
                       'rounded-lg border-2 px-3 py-1 font-mono text-xs font-semibold transition-colors',
                       'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-500 dark:text-emerald-950' => ! isset($currentCategory),
                       'border-zinc-200 text-zinc-600 hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-200' => isset($currentCategory),
                   ])>
                    {{ __('blog::messages.all') }}
                </a>
                @foreach($categories as $category)
                    @php $isCurrent = isset($currentCategory) && $currentCategory->is($category); @endphp
                    <a href="{{ route('blog.category', $category) }}"
                       @class([
                           'rounded-lg border-2 px-3 py-1 font-mono text-xs font-semibold transition-colors',
                           'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-500 dark:text-emerald-950' => $isCurrent,
                           'border-zinc-200 text-zinc-600 hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-200' => ! $isCurrent,
                       ])>
                        {{ $category->name }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if($posts->isEmpty())
            <div class="py-12 text-center">
                <p class="mx-auto w-fit -rotate-1 border-2 border-zinc-900 bg-emerald-400 px-6 py-4 font-mono text-sm font-semibold text-zinc-900 shadow-[4px_4px_0_0_theme(colors.zinc.900)] dark:border-zinc-100 dark:shadow-[4px_4px_0_0_theme(colors.zinc.100)]">
                    {{ __('blog::messages.no_posts_yet') }}
                </p>
            </div>
        @else
            <ul class="divide-y-2 divide-zinc-100 dark:divide-zinc-800">
                @foreach($posts as $post)
                    <li class="py-7 first:pt-0">
                        <a href="{{ $post->url() }}" class="group flex gap-5 sm:gap-6">
                            <div class="min-w-0 flex-1">
                                <span class="font-mono text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400">
                                    {{ $post->category->name }}
                                </span>
                                <h2 class="mt-1.5 mb-1.5 text-lg font-bold leading-snug tracking-tight text-zinc-900 underline-offset-4 transition group-hover:underline group-hover:decoration-emerald-500 group-hover:decoration-[3px] sm:text-xl dark:text-zinc-100">
                                    {{ $post->title }}
                                </h2>
                                <p class="mb-3 line-clamp-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                    {{ $post->excerpt }}
                                </p>
                                <div class="flex items-center gap-2 font-mono text-xs text-zinc-500 dark:text-zinc-500">
                                    @if ($post->authorAvatarUrl())
                                        <img src="{{ $post->authorAvatarUrl() }}" alt="" class="size-[24px] shrink-0 rounded-full border border-zinc-300 object-cover dark:border-zinc-600" />
                                    @else
                                        <span aria-hidden="true" class="flex size-[24px] shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">{{ $post->authorInitials() }}</span>
                                    @endif
                                    <span>{{ $post->authorName() }}</span>
                                    <span aria-hidden="true" class="text-emerald-500">✦</span>
                                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                                        {{ $post->published_at->translatedFormat('M j, Y') }}
                                    </time>
                                    <span aria-hidden="true" class="text-emerald-500">✦</span>
                                    <span>{{ $post->reading_time }} {{ __('blog::messages.min_read') }}</span>
                                </div>
                            </div>
                            @if($post->cover_image)
                                <div class="hidden shrink-0 sm:block">
                                    <img src="{{ $post->cover_image }}" alt=""
                                         class="h-24 w-24 rotate-1 rounded-lg border-2 border-zinc-200 object-cover shadow-[3px_3px_0_0_theme(colors.zinc.300)] transition group-hover:rotate-0 dark:border-zinc-700 dark:shadow-[3px_3px_0_0_theme(colors.zinc.700)]">
                                </div>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if(config('blog.cta_view'))
            <div class="mt-12">
                @includeIf(config('blog.cta_view'))
            </div>
        @endif
    </div>
@endsection
