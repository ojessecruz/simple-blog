@extends(config('blog.layouts.public'))

@push('head')
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
@endpush

@section('content')
    <article class="max-w-2xl mx-auto px-5 sm:px-6 py-12">

        {{-- Back --}}
        <a href="{{ route('blog.index') }}"
           class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 inline-flex items-center gap-1 mb-8 transition-colors">
            <x-blog::icon.arrow-left class="w-4 h-4" />
            Back
        </a>

        {{-- Header --}}
        <header class="mb-8">
            <a href="{{ route('blog.category', $post->category) }}"
               class="text-xs uppercase tracking-wide text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
                {{ $post->category->name }}
            </a>

            <h1 class="text-3xl sm:text-4xl font-semibold mt-2 leading-tight tracking-tight">
                {{ $post->title }}
            </h1>

            @if($post->excerpt)
                <p class="mt-3 text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    {{ $post->excerpt }}
                </p>
            @endif

            <div class="mt-5 text-sm text-zinc-500 dark:text-zinc-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="text-zinc-700 dark:text-zinc-300 font-medium">{{ $post->authorName() }}</span>
                @if($post->published_at)
                    <span aria-hidden="true">·</span>
                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                        {{ $post->published_at->translatedFormat('M j, Y') }}
                    </time>
                @endif
                <span aria-hidden="true">·</span>
                <span>{{ $post->reading_time }} min read</span>
            </div>
        </header>

        @if(! $post->isPublished())
            <div class="mb-8 rounded border border-amber-300/60 dark:border-amber-700/40 bg-amber-50 dark:bg-amber-950/30 px-3 py-2 text-sm text-amber-800 dark:text-amber-300">
                <strong class="font-semibold">Preview:</strong>
                @if($post->published_at)
                    scheduled for {{ $post->published_at->translatedFormat('F j, Y \a\t H:i') }}.
                @else
                    draft.
                @endif
            </div>
        @endif

        {{-- Cover --}}
        @if($post->cover_image)
            <img src="{{ $post->cover_image }}" alt="{{ $post->title }}"
                 class="w-full h-auto rounded-lg mb-8 object-cover" />
        @endif

        {{-- Content --}}
        <div class="blog-content">
            {!! $post->renderedBody() !!}
        </div>

        <style>
            .blog-content { color: #27272a; font-size: 1.0625rem; line-height: 1.75; }
            .blog-content > * + * { margin-top: 1.25em; }
            .blog-content h2 { font-size: 1.5rem; font-weight: 600; line-height: 1.3; margin-top: 2em; margin-bottom: 0.5em; color: #18181b; letter-spacing: -0.01em; }
            .blog-content h3 { font-size: 1.25rem; font-weight: 600; line-height: 1.35; margin-top: 1.75em; margin-bottom: 0.4em; color: #18181b; }
            .blog-content h2 + p, .blog-content h3 + p { margin-top: 0; }
            .blog-content strong { color: #18181b; font-weight: 600; }
            .blog-content a { color: #059669; text-decoration: underline; text-underline-offset: 3px; }
            .blog-content a:hover { color: #047857; }
            .blog-content ul, .blog-content ol { padding-left: 1.5rem; }
            .blog-content ul { list-style: disc; }
            .blog-content ol { list-style: decimal; }
            .blog-content li + li { margin-top: 0.4em; }
            .blog-content blockquote { border-left: 3px solid #d4d4d8; padding-left: 1rem; color: #52525b; font-style: italic; }
            .blog-content code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #f4f4f5; color: #18181b; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875em; }
            .blog-content pre { background: #fafafa; border: 1px solid #e4e4e7; border-radius: 0.375rem; padding: 0.875rem 1rem; overflow-x: auto; font-size: 0.875rem; line-height: 1.6; }
            .blog-content pre code { background: transparent; padding: 0; font-size: inherit; }
            .blog-content img { max-width: 100%; height: auto; border-radius: 0.375rem; }
            .blog-content table { width: 100%; border-collapse: collapse; font-size: 0.9375rem; }
            .blog-content th, .blog-content td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #e4e4e7; text-align: left; }
            .blog-content th { font-weight: 600; color: #18181b; background: #fafafa; }
            .blog-content hr { border: 0; border-top: 1px solid #e4e4e7; margin: 2rem 0; }

            .dark .blog-content { color: #d4d4d8; }
            .dark .blog-content h2,
            .dark .blog-content h3,
            .dark .blog-content strong { color: #fafafa; }
            .dark .blog-content a { color: #34d399; }
            .dark .blog-content a:hover { color: #6ee7b7; }
            .dark .blog-content blockquote { border-left-color: #3f3f46; color: #a1a1aa; }
            .dark .blog-content code { background: #27272a; color: #f4f4f5; }
            .dark .blog-content pre { background: #18181b; border-color: #27272a; }
            .dark .blog-content th { color: #fafafa; background: #18181b; }
            .dark .blog-content th, .dark .blog-content td { border-bottom-color: #27272a; }
            .dark .blog-content hr { border-top-color: #3f3f46; }
        </style>

        {{-- Footer --}}
        <div class="mt-12 pt-6 border-t border-zinc-200 dark:border-zinc-800 flex justify-between items-center text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('blog.category', $post->category) }}"
               class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                More in {{ $post->category->name }}
            </a>

            <div class="flex items-center gap-4">
                <a href="https://wa.me/?text={{ urlencode($post->title.' - '.$post->url()) }}" target="_blank" rel="noopener"
                   class="hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors" title="WhatsApp">
                    <x-blog::icon.whatsapp class="w-4 h-4" />
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"
                   class="hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors" title="X (Twitter)">
                    <x-blog::icon.x-twitter class="w-4 h-4" />
                </a>
            </div>
        </div>

        @if(config('blog.cta_view'))
            <div class="mt-10">
                @includeIf(config('blog.cta_view'))
            </div>
        @endif

        @if($relatedPosts->isNotEmpty())
            <section class="mt-12 pt-6 border-t border-zinc-200 dark:border-zinc-800">
                <h2 class="text-sm font-semibold mb-3 text-zinc-700 dark:text-zinc-300">Related</h2>
                <ul class="space-y-2 text-sm">
                    @foreach($relatedPosts as $related)
                        <li>
                            <a href="{{ $related->url() }}"
                               class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
                                {{ $related->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
@endsection
