@extends(config('blog.layouts.public'))

@section('content')
    <article class="max-w-2xl mx-auto px-4 sm:px-6 pt-10 pb-16">
        <div class="mb-6">
            <a href="{{ route('blog.index') }}"
               class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 inline-flex items-center gap-1 transition-colors">
                <x-blog::icon.arrow-left class="w-4 h-4" />
                Back to blog
            </a>
        </div>

        <header class="mb-6">
            <a href="{{ route('blog.category', $post->category) }}"
               class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
                {{ $post->category->name }}
            </a>
            <h1 class="text-3xl sm:text-4xl font-bold mt-2 mb-3 leading-tight">
                {{ $post->title }}
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400 leading-snug">
                {{ $post->excerpt }}
            </p>
        </header>

        @if(! $post->isPublished())
            <div class="mb-6 rounded border border-amber-300/60 dark:border-amber-700/40 bg-amber-50 dark:bg-amber-950/30 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
                <strong class="font-semibold">Preview:</strong>
                @if($post->published_at)
                    scheduled for {{ $post->published_at->translatedFormat('F j, Y \a\t H:i') }}.
                @else
                    this post is still a draft.
                @endif
            </div>
        @endif

        <div class="flex items-center justify-between gap-4 flex-wrap pb-6 mb-8 border-b border-zinc-200 dark:border-zinc-800 text-sm text-zinc-500 dark:text-zinc-400">
            <div class="flex items-center gap-2">
                <span class="text-zinc-700 dark:text-zinc-300 font-medium">{{ $post->authorName() }}</span>
                @if($post->published_at)
                    <span aria-hidden="true">&middot;</span>
                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                        {{ $post->published_at->translatedFormat('F j, Y') }}
                    </time>
                @endif
                <span aria-hidden="true">&middot;</span>
                <span>{{ $post->reading_time }} min read</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors" title="X (Twitter)">
                    <x-blog::icon.x-twitter class="h-4 w-4" />
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode($post->url()) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-blue-500 transition-colors" title="LinkedIn">
                    <x-blog::icon.linkedin class="h-4 w-4" />
                </a>
            </div>
        </div>

        @if($post->cover_image)
            <figure class="mb-8">
                <img src="{{ $post->cover_image }}" alt="{{ $post->title }}" class="w-full h-auto rounded">
            </figure>
        @endif

        <div class="blog-content">
            {!! $post->renderedBody() !!}
        </div>

        <style>
            .blog-content { color: #27272a; line-height: 1.7; font-size: 1.0625rem; }
            .blog-content > * + * { margin-top: 1.1em; }
            .blog-content > h2 + p,
            .blog-content > h3 + p { margin-top: 0.5em; }
            .blog-content h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2em; line-height: 1.3; color: #18181b; }
            .blog-content h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.75em; line-height: 1.3; color: #18181b; }
            .blog-content p { color: #27272a; }
            .blog-content strong { color: #18181b; font-weight: 600; }
            .blog-content em { font-style: italic; }
            .blog-content a { color: #059669; text-decoration: underline; text-underline-offset: 2px; }
            .blog-content a:hover { color: #047857; }
            .blog-content ul, .blog-content ol { padding-left: 1.5rem; }
            .blog-content ul { list-style-type: disc; }
            .blog-content ol { list-style-type: decimal; }
            .blog-content li + li { margin-top: 0.4em; }
            .blog-content blockquote { border-left: 3px solid #d4d4d8; padding-left: 1rem; color: #52525b; margin: 1.4em 0; }
            .blog-content code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #f4f4f5; color: #18181b; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875em; }
            .blog-content pre { background: #fafafa; border: 1px solid #e4e4e7; border-radius: 0.375rem; padding: 0.875rem 1rem; overflow-x: auto; font-size: 0.875rem; line-height: 1.5; }
            .blog-content pre code { background: transparent; padding: 0; font-size: inherit; }
            .blog-content img { max-width: 100%; height: auto; border-radius: 0.25rem; margin: 1.4em 0; }
            .blog-content figure { margin: 1.4em 0; }
            .blog-content figcaption { font-size: 0.875rem; color: #71717a; margin-top: 0.5rem; text-align: center; }
            .blog-content table { width: 100%; border-collapse: collapse; font-size: 0.9375rem; }
            .blog-content th { background: #fafafa; color: #18181b; padding: 0.5rem 0.75rem; text-align: left; border-bottom: 2px solid #e4e4e7; font-weight: 600; }
            .blog-content td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #e4e4e7; color: #27272a; }
            .blog-content hr { border: 0; border-top: 1px solid #e4e4e7; margin: 2rem 0; }

            .dark .blog-content { color: #d4d4d8; }
            .dark .blog-content h2,
            .dark .blog-content h3 { color: #fafafa; }
            .dark .blog-content p { color: #d4d4d8; }
            .dark .blog-content strong { color: #fff; }
            .dark .blog-content a { color: #34d399; }
            .dark .blog-content a:hover { color: #6ee7b7; }
            .dark .blog-content blockquote { border-left-color: #3f3f46; color: #a1a1aa; }
            .dark .blog-content code { background: #27272a; color: #f4f4f5; }
            .dark .blog-content pre { background: #18181b; border-color: #27272a; }
            .dark .blog-content figcaption { color: #a1a1aa; }
            .dark .blog-content th { background: #18181b; color: #fafafa; border-bottom-color: #3f3f46; }
            .dark .blog-content td { color: #d4d4d8; border-bottom-color: #27272a; }
            .dark .blog-content hr { border-top-color: #3f3f46; }
        </style>

        <div class="mt-12 pt-6 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between flex-wrap gap-3 text-sm">
            <a href="{{ route('blog.category', $post->category) }}"
               class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
                More in {{ $post->category->name }}
            </a>
            <div class="flex items-center gap-4">
                <a href="https://wa.me/?text={{ urlencode($post->title.' - '.$post->url()) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-green-500 transition-colors" title="WhatsApp">
                    <x-blog::icon.whatsapp class="h-4 w-4" />
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors" title="X (Twitter)">
                    <x-blog::icon.x-twitter class="h-4 w-4" />
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode($post->url()) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-blue-500 transition-colors" title="LinkedIn">
                    <x-blog::icon.linkedin class="h-4 w-4" />
                </a>
            </div>
        </div>

        @if(config('blog.cta_view'))
            <div class="mt-12">
                @includeIf(config('blog.cta_view'))
            </div>
        @endif

        @if($relatedPosts->isNotEmpty())
            <section class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800">
                <h2 class="text-lg font-bold mb-6">Continue reading</h2>
                <ul class="space-y-6">
                    @foreach($relatedPosts as $related)
                        <li>
                            <a href="{{ $related->url() }}" class="group block">
                                <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ $related->category->name }}</span>
                                <h3 class="text-base font-semibold mt-1 mb-1 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                    {{ $related->title }}
                                </h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $related->excerpt }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
@endsection
