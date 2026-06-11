@extends(config('blog.layouts.public'))

@push('head')
    <title>@if(isset($currentCategory)){{ $currentCategory->name }} — @endif Blog</title>
    @if(isset($currentCategory) && $currentCategory->description)
        <meta name="description" content="{{ $currentCategory->description }}">
    @endif
@endpush

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
        <header class="mb-10">
            @if(isset($currentCategory))
                <p class="mb-3 font-mono text-xs font-semibold uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">
                    {{ __('blog::messages.category') }}
                </p>
            @endif

            <h1 class="font-serif text-4xl italic sm:text-5xl text-zinc-900 dark:text-zinc-100">
                {{ isset($currentCategory) ? $currentCategory->name : __('blog::messages.blog') }}
            </h1>
            <x-blog::squiggle class="mt-2 h-2.5 w-24 text-emerald-500" />

            <p class="mt-5 text-base text-zinc-600 dark:text-zinc-400">
                @if(isset($currentCategory) && $currentCategory->description)
                    {{ $currentCategory->description }}
                @else
                    {{ __('blog::messages.latest_posts') }}
                @endif
            </p>
        </header>

        @if($categories->isNotEmpty())
            <nav class="mb-10 flex flex-wrap gap-2">
                <a href="{{ route('blog.index') }}"
                   class="{{ ! isset($currentCategory)
                        ? 'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-500 dark:text-emerald-950'
                        : 'border-zinc-200 text-zinc-600 hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-200' }} rounded-lg border px-3 py-1 font-mono text-xs font-semibold transition-colors">
                    {{ __('blog::messages.all') }}
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('blog.category', $category) }}"
                       class="{{ (isset($currentCategory) && $currentCategory->is($category))
                            ? 'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-500 dark:bg-emerald-500 dark:text-emerald-950'
                            : 'border-zinc-200 text-zinc-600 hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-200' }} rounded-lg border px-3 py-1 font-mono text-xs font-semibold transition-colors">
                        {{ $category->name }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if($posts->isEmpty())
            <div class="py-12 text-center">
                <p class="inline-block rounded-lg border border-dashed border-zinc-300 px-6 py-4 font-mono text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    {{ __('blog::messages.no_posts_yet') }}
                </p>
            </div>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($posts as $post)
                    <li class="py-7 first:pt-0">
                        <a href="{{ $post->url() }}" class="group flex gap-5 sm:gap-6">
                            <div class="flex-1 min-w-0">
                                <span class="font-mono text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400">
                                    {{ $post->category->name }}
                                </span>
                                <h2 class="mt-1.5 mb-1.5 text-lg font-bold leading-snug text-zinc-900 underline-offset-4 group-hover:underline group-hover:decoration-emerald-500 group-hover:decoration-[3px] sm:text-xl dark:text-zinc-100">
                                    {{ $post->title }}
                                </h2>
                                <p class="mb-3 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $post->excerpt }}
                                </p>
                                <div class="flex items-center gap-2 font-mono text-xs text-zinc-500 dark:text-zinc-500">
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
                                    <img src="{{ $post->cover_image }}" alt="" class="h-24 w-24 rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
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
