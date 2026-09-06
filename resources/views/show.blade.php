@extends(config('blog.layouts.public'))

@php
    $seoTitle = \Jessecruz\SimpleBlog\Support\Seo::postTitle($post);
    $seoDescription = \Jessecruz\SimpleBlog\Support\Seo::postDescription($post);
    $seoImage = \Jessecruz\SimpleBlog\Support\Seo::postImage($post);
@endphp

@push('head')
    <title>{{ $seoTitle }}</title>
    @if($seoDescription)
        <meta name="description" content="{{ $seoDescription }}">
        <meta property="og:description" content="{{ $seoDescription }}">
    @endif
    @if($post->keywords)
        <meta name="keywords" content="{{ implode(', ', $post->keywords) }}">
    @endif
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:type" content="article">
    @if($seoImage)
        <meta property="og:image" content="{{ $seoImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif
    @if($post->published_at)
        <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
        <meta property="article:modified_time" content="{{ ($post->updated_at ?? $post->published_at)->toIso8601String() }}">
    @endif
    @if($post->isPublished())
        <script type="application/ld+json">{!! json_encode(\Jessecruz\SimpleBlog\Support\Seo::articleSchema($post), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@endpush

@section('content')
    <article class="mx-auto max-w-2xl px-5 py-12 sm:px-6">

        {{-- Back --}}
        <a href="{{ route('blog.index') }}"
           class="group mb-10 inline-flex items-center gap-1.5 font-mono text-xs font-semibold tracking-wide text-zinc-500 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200">
            <x-blog::icon.arrow-left class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" />
            {{ __('blog::messages.back') }}
        </a>

        {{-- Header --}}
        <header class="mb-10">
            <a href="{{ route('blog.category', $post->category) }}"
               class="font-mono text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-600 underline-offset-4 transition hover:underline hover:decoration-emerald-400 hover:decoration-2 dark:text-emerald-400">
                {{ $post->category->name }}
            </a>

            <h1 class="mt-3 text-3xl font-bold leading-tight tracking-tight text-zinc-900 sm:text-4xl dark:text-zinc-100">
                {{ $post->title }}
            </h1>

            @if($post->excerpt)
                <p class="mt-4 font-serif text-xl italic leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ $post->excerpt }}
                </p>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-2 font-mono text-xs text-zinc-500 dark:text-zinc-400">
                @if ($post->authorAvatarUrl())
                    <img src="{{ $post->authorAvatarUrl() }}" alt="" class="size-[24px] shrink-0 rounded-full border border-emerald-500 object-cover" />
                @else
                    <span aria-hidden="true" class="flex size-[24px] shrink-0 items-center justify-center rounded-full border border-emerald-500 bg-emerald-50 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ $post->authorInitials() }}</span>
                @endif
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $post->authorName() }}</span>
                @if($post->published_at)
                    <span aria-hidden="true" class="text-emerald-500">✦</span>
                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                        {{ $post->published_at->translatedFormat('M j, Y') }}
                    </time>
                @endif
                <span aria-hidden="true" class="text-emerald-500">✦</span>
                <span>{{ $post->reading_time }} {{ __('blog::messages.min_read') }}</span>
            </div>
        </header>

        @if(! $post->isPublished())
            <div class="mb-8 -rotate-[0.5deg] border-2 border-amber-500 bg-amber-50 px-4 py-3 font-mono text-sm text-amber-900 shadow-[3px_3px_0_0_#f59e0b] dark:border-amber-400 dark:bg-amber-950/30 dark:text-amber-300">
                <strong class="font-bold">{{ __('blog::messages.preview_label') }}</strong>
                @if($post->published_at)
                    {{ __('blog::messages.preview_scheduled', ['date' => $post->published_at->translatedFormat('F j, Y \a\t H:i')]) }}
                @else
                    {{ __('blog::messages.preview_draft') }}
                @endif
            </div>
        @endif

        {{-- Cover --}}
        @if($post->cover_image)
            <figure class="my-10 lg:my-12">
                <img src="{{ $post->cover_image }}"
                     alt="{{ $post->title }}"
                     class="aspect-[16/9] w-full rounded-2xl border-2 border-zinc-200 object-cover shadow-[6px_6px_0_0_theme(colors.zinc.200)] dark:border-zinc-700 dark:shadow-[6px_6px_0_0_theme(colors.zinc.700)]" />
            </figure>
        @endif

        {{-- Content --}}
        <div class="blog-content">
            {!! $post->renderedBody() !!}
        </div>

        <style>
            .blog-content { color: #27272a; font-size: 1.0625rem; line-height: 1.75; }
            .blog-content > * + * { margin-top: 1.25em; }
            .blog-content h2 { font-size: 1.5rem; font-weight: 700; line-height: 1.3; margin-top: 2em; margin-bottom: 0.5em; color: #18181b; letter-spacing: -0.01em; }
            .blog-content h3 { font-size: 1.25rem; font-weight: 700; line-height: 1.35; margin-top: 1.75em; margin-bottom: 0.4em; color: #18181b; }
            .blog-content h2 + p, .blog-content h3 + p { margin-top: 0; }
            .blog-content strong { color: #18181b; font-weight: 600; }
            .blog-content a { color: #18181b; font-weight: 500; text-decoration: underline; text-decoration-color: #10b981; text-decoration-thickness: 2px; text-underline-offset: 3px; }
            .blog-content a:hover { text-decoration-thickness: 3px; }
            .blog-content ul, .blog-content ol { padding-left: 1.5rem; }
            .blog-content ul { list-style: disc; }
            .blog-content ol { list-style: decimal; }
            .blog-content li::marker { color: #10b981; }
            .blog-content li + li { margin-top: 0.4em; }
            .blog-content blockquote { border-left: 3px solid #10b981; padding-left: 1rem; color: #52525b; font-family: ui-serif, Georgia, serif; font-style: italic; font-size: 1.075em; }
            .blog-content code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #f4f4f5; color: #18181b; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875em; }
            .blog-content pre { background: #fafafa; border: 2px solid #e4e4e7; border-radius: 0.75rem; padding: 0.875rem 1rem; overflow-x: auto; font-size: 0.875rem; line-height: 1.6; }
            .blog-content pre code { background: transparent; padding: 0; font-size: inherit; }
            .blog-content img { max-width: 100%; height: auto; border-radius: 0.75rem; border: 2px solid #e4e4e7; }
            .blog-content table { width: 100%; border-collapse: collapse; font-size: 0.9375rem; }
            .blog-content th, .blog-content td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #e4e4e7; text-align: left; }
            .blog-content th { font-weight: 600; color: #18181b; background: #fafafa; }
            .blog-content hr { border: 0; border-top: 2px dashed #e4e4e7; margin: 2rem 0; }

            .dark .blog-content { color: #d4d4d8; }
            .dark .blog-content h2,
            .dark .blog-content h3,
            .dark .blog-content strong { color: #fafafa; }
            .dark .blog-content a { color: #fafafa; text-decoration-color: #34d399; }
            .dark .blog-content li::marker { color: #34d399; }
            .dark .blog-content blockquote { border-left-color: #34d399; color: #a1a1aa; }
            .dark .blog-content code { background: #27272a; color: #f4f4f5; }
            .dark .blog-content pre { background: #18181b; border-color: #27272a; }
            .dark .blog-content img { border-color: #3f3f46; }
            .dark .blog-content th { color: #fafafa; background: #18181b; }
            .dark .blog-content th, .dark .blog-content td { border-bottom-color: #27272a; }
            .dark .blog-content hr { border-top-color: #3f3f46; }
        </style>

        {{-- Footer --}}
        <div class="mt-14 flex items-center justify-between border-t-2 border-zinc-100 pt-6 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
            <a href="{{ route('blog.category', $post->category) }}"
               class="underline-offset-4 transition hover:text-zinc-900 hover:underline hover:decoration-emerald-500 hover:decoration-2 dark:hover:text-zinc-200">
                {{ __('blog::messages.more_in', ['name' => $post->category->name]) }}
            </a>

            <div class="flex items-center gap-3">
                <a href="https://wa.me/?text={{ urlencode($post->title.' - '.$post->url()) }}" target="_blank" rel="noopener"
                   class="rounded-lg border-2 border-zinc-200 p-2 text-zinc-500 transition hover:-rotate-6 hover:border-emerald-500 hover:text-emerald-600 dark:border-zinc-700 dark:hover:border-emerald-500 dark:hover:text-emerald-400" title="WhatsApp">
                    <x-blog::icon.whatsapp class="h-4 w-4" />
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"
                   class="rounded-lg border-2 border-zinc-200 p-2 text-zinc-500 transition hover:-rotate-6 hover:border-emerald-500 hover:text-emerald-600 dark:border-zinc-700 dark:hover:border-emerald-500 dark:hover:text-emerald-400" title="X (Twitter)">
                    <x-blog::icon.x-twitter class="h-4 w-4" />
                </a>
            </div>
        </div>

        @if(config('blog.cta_view'))
            <div class="mt-10">
                @includeIf(config('blog.cta_view'))
            </div>
        @endif

        @if($relatedPosts->isNotEmpty())
            <section class="mt-12 border-t-2 border-zinc-100 pt-6 dark:border-zinc-800">
                <h2 class="mb-4 font-mono text-xs font-semibold uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">
                    {{ __('blog::messages.related') }}
                </h2>
                <ul class="space-y-2.5 text-sm">
                    @foreach($relatedPosts as $related)
                        <li class="flex items-start gap-2">
                            <span aria-hidden="true" class="mt-0.5 text-emerald-500">✦</span>
                            <a href="{{ $related->url() }}"
                               class="font-semibold text-zinc-900 underline-offset-4 transition hover:underline hover:decoration-emerald-500 hover:decoration-2 dark:text-zinc-100">
                                {{ $related->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
@endsection
