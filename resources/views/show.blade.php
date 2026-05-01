@extends(config('blog.layouts.public'))

@section('content')
    @if($post->cover_image)
        <figure class="w-full max-h-[460px] overflow-hidden bg-zinc-100 dark:bg-zinc-900">
            <img src="{{ $post->cover_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
        </figure>
    @endif

    <article class="max-w-[44rem] mx-auto px-4 sm:px-6 pt-12 pb-16 sm:pt-16">
        <div class="mb-6">
            <a href="{{ route('blog.category', $post->category) }}"
               class="inline-block text-xs font-medium uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
                {{ $post->category->name }}
            </a>
        </div>

        <header class="mb-10">
            <h1 class="blog-serif text-[2.25rem] sm:text-5xl lg:text-[3.25rem] font-bold mb-5 leading-[1.15] tracking-tight">
                {{ $post->title }}
            </h1>
            <p class="text-xl sm:text-2xl text-zinc-500 dark:text-zinc-400 leading-snug font-light">
                {{ $post->excerpt }}
            </p>
        </header>

        @if(! $post->isPublished())
            <div class="mb-8 rounded-lg border border-amber-300/60 dark:border-amber-700/40 bg-amber-50 dark:bg-amber-950/30 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
                <strong class="font-semibold">Pré-visualização:</strong>
                @if($post->published_at)
                    agendado para {{ $post->published_at->translatedFormat('d \d\e F \d\e Y \à\s H:i') }}.
                @else
                    este post ainda é um rascunho.
                @endif
            </div>
        @endif

        <div class="flex items-center gap-3 mb-12 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-semibold flex items-center justify-center text-sm shrink-0">
                {{ $post->authorInitials() }}
            </div>
            <div class="flex-1 min-w-0 text-sm">
                <div class="text-zinc-900 dark:text-zinc-200 font-medium">{{ $post->authorName() }}</div>
                <div class="text-zinc-500 dark:text-zinc-400">
                    @if($post->published_at)
                        <time datetime="{{ $post->published_at->toIso8601String() }}">
                            {{ $post->published_at->translatedFormat('d \d\e F \d\e Y') }}
                        </time>
                        <span aria-hidden="true" class="mx-1">&middot;</span>
                    @endif
                    <span>{{ $post->reading_time }} min de leitura</span>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors" title="X (Twitter)">
                    <x-blog::icon.x-twitter class="h-5 w-5" />
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode($post->url()) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-blue-500 transition-colors" title="LinkedIn">
                    <x-blog::icon.linkedin class="h-5 w-5" />
                </a>
            </div>
        </div>

        <div class="blog-content">
            {!! $post->renderedBody() !!}
        </div>

        <div class="my-12 flex items-center justify-center gap-3 text-zinc-300 dark:text-zinc-700" aria-hidden="true">
            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
        </div>

        <style>
            .blog-content {
                color: #242424;
                font-family: "Source Serif Pro", ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
                line-height: 1.65;
                font-size: 1.3125rem;
                letter-spacing: -0.003em;
            }
            .blog-content > * + * { margin-top: 1.4em; }
            .blog-content > h2 + p,
            .blog-content > h3 + p { margin-top: 0.6em; }
            .blog-content h2 { font-size: 2rem; font-weight: 700; margin-top: 2.4em; line-height: 1.2; letter-spacing: -0.018em; color: #111; }
            .blog-content h3 { font-size: 1.5rem; font-weight: 600; margin-top: 2em; line-height: 1.25; letter-spacing: -0.012em; color: #111; }
            .blog-content p { color: #242424; }
            .blog-content > p:first-of-type::first-letter {
                font-size: 4.25rem;
                font-weight: 700;
                line-height: 0.85;
                float: left;
                margin: 0.1em 0.1em 0 0;
                color: #111;
            }
            .blog-content strong { color: #111; font-weight: 600; }
            .blog-content em { font-style: italic; }
            .blog-content a { color: #242424; text-decoration: underline; text-underline-offset: 4px; text-decoration-thickness: 1px; }
            .blog-content a:hover { color: #059669; }
            .blog-content ul, .blog-content ol { padding-left: 1.6rem; }
            .blog-content ul { list-style-type: disc; }
            .blog-content ol { list-style-type: decimal; }
            .blog-content li + li { margin-top: 0.5em; }
            .blog-content blockquote { border-left: 3px solid #111; padding-left: 1.5rem; color: #444; font-style: italic; font-size: 1.5rem; line-height: 1.4; margin-left: -0.25rem; }
            .blog-content code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #f3f4f6; color: #111; padding: 0.125rem 0.4rem; border-radius: 0.25rem; font-size: 0.85em; }
            .blog-content pre { background: #fafafa; border: 1px solid #eee; border-radius: 0.5rem; padding: 1rem 1.25rem; overflow-x: auto; font-size: 0.95rem; line-height: 1.55; }
            .blog-content pre code { background: transparent; padding: 0; font-size: inherit; }
            .blog-content img { border-radius: 0.25rem; max-width: 100%; height: auto; }
            .blog-content figure { text-align: center; }
            .blog-content figcaption { font-size: 0.875rem; color: #6b7280; margin-top: 0.75rem; font-family: ui-sans-serif, system-ui, sans-serif; font-style: italic; }
            .blog-content table { width: 100%; border-collapse: collapse; font-size: 1rem; }
            .blog-content th { background: #fafafa; color: #111; padding: 0.75rem 1rem; text-align: left; border-bottom: 2px solid #e5e7eb; font-weight: 600; font-family: ui-sans-serif, system-ui, sans-serif; }
            .blog-content td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; color: #242424; }
            .blog-content hr { border: 0; height: auto; margin: 3rem auto; width: 6rem; text-align: center; color: #d1d5db; }
            .blog-content hr::before { content: "\00b7  \00b7  \00b7"; letter-spacing: 0.5em; font-size: 1.5rem; }

            .dark .blog-content { color: #d4d4d4; }
            .dark .blog-content h2,
            .dark .blog-content h3 { color: #fafafa; }
            .dark .blog-content p { color: #d4d4d4; }
            .dark .blog-content > p:first-of-type::first-letter { color: #fafafa; }
            .dark .blog-content strong { color: #fff; }
            .dark .blog-content a { color: #d4d4d4; }
            .dark .blog-content a:hover { color: #34d399; }
            .dark .blog-content blockquote { border-left-color: #d4d4d4; color: #a3a3a3; }
            .dark .blog-content code { background: #1f2937; color: #f3f4f6; }
            .dark .blog-content pre { background: #111827; border-color: #1f2937; }
            .dark .blog-content figcaption { color: #9ca3af; }
            .dark .blog-content th { background: #0f172a; color: #fafafa; border-bottom-color: #1f2937; }
            .dark .blog-content td { color: #d4d4d4; border-bottom-color: #1f2937; }
            .dark .blog-content hr { color: #374151; }
        </style>

        <div class="mt-16 pt-6 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between flex-wrap gap-3">
            <a href="{{ route('blog.category', $post->category) }}"
               class="inline-block text-xs font-medium uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
                Mais em {{ $post->category->name }}
            </a>
            <div class="flex items-center gap-4">
                <a href="https://wa.me/?text={{ urlencode($post->title.' - '.$post->url()) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-green-500 transition-colors" title="WhatsApp">
                    <x-blog::icon.whatsapp class="h-5 w-5" />
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($post->url()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors" title="X (Twitter)">
                    <x-blog::icon.x-twitter class="h-5 w-5" />
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode($post->url()) }}" target="_blank" rel="noopener"
                   class="text-zinc-400 hover:text-blue-500 transition-colors" title="LinkedIn">
                    <x-blog::icon.linkedin class="h-5 w-5" />
                </a>
            </div>
        </div>

        @if(config('blog.cta_view'))
            <div class="mt-16">
                @includeIf(config('blog.cta_view'))
            </div>
        @endif

        @if($relatedPosts->isNotEmpty())
            <section class="mt-20 pt-10 border-t border-zinc-200 dark:border-zinc-800">
                <h2 class="blog-serif text-2xl font-bold mb-8 tracking-tight">Continue lendo</h2>
                <div class="space-y-10">
                    @foreach($relatedPosts as $related)
                        <a href="{{ $related->url() }}" class="group block">
                            <span class="text-xs font-medium uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-400">{{ $related->category->name }}</span>
                            <h3 class="blog-serif text-xl sm:text-2xl font-bold mt-2 mb-2 leading-snug group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors">
                                {{ $related->title }}
                            </h3>
                            <p class="text-base text-zinc-500 dark:text-zinc-400 line-clamp-2 mb-2">{{ $related->excerpt }}</p>
                            <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ $related->reading_time }} min de leitura</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
