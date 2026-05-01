@extends(config('blog.layouts.public'))

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
        <header class="mb-12">
            <h1 class="blog-serif text-4xl sm:text-5xl font-bold mb-3 tracking-tight">
                @if(isset($currentCategory))
                    {{ $currentCategory->name }}
                @else
                    Blog
                @endif
            </h1>
            <p class="text-lg text-zinc-500 dark:text-zinc-400">
                @if(isset($currentCategory) && $currentCategory->description)
                    {{ $currentCategory->description }}
                @else
                    Últimas publicações.
                @endif
            </p>
        </header>

        @if($categories->isNotEmpty())
            <nav class="flex flex-wrap gap-x-6 gap-y-2 mb-12 pb-6 border-b border-zinc-200 dark:border-zinc-800 text-sm">
                <a href="{{ route('blog.index') }}"
                   class="{{ ! isset($currentCategory) ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }} transition-colors">
                    Todos
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('blog.category', $category) }}"
                       class="{{ (isset($currentCategory) && $currentCategory->is($category)) ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }} transition-colors">
                        {{ $category->name }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if($posts->isEmpty())
            <div class="text-center py-16 text-zinc-500 dark:text-zinc-400">
                <p>Em breve teremos novos conteúdos.</p>
            </div>
        @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($posts as $post)
                    <article class="py-10 first:pt-0">
                        <a href="{{ $post->url() }}" class="group block">
                            <div class="flex gap-6 sm:gap-10">
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                        {{ $post->category->name }}
                                    </span>
                                    <h2 class="blog-serif text-2xl sm:text-3xl font-bold mt-2 mb-3 leading-tight group-hover:text-zinc-600 dark:group-hover:text-zinc-300 transition-colors">
                                        {{ $post->title }}
                                    </h2>
                                    <p class="text-zinc-500 dark:text-zinc-400 text-base sm:text-lg mb-4 line-clamp-2">
                                        {{ $post->excerpt }}
                                    </p>
                                    <div class="flex items-center gap-3 text-sm text-zinc-400 dark:text-zinc-500">
                                        <span>{{ $post->authorName() }}</span>
                                        <span aria-hidden="true">&middot;</span>
                                        <time datetime="{{ $post->published_at->toIso8601String() }}">
                                            {{ $post->published_at->translatedFormat('d M Y') }}
                                        </time>
                                        <span aria-hidden="true">&middot;</span>
                                        <span>{{ $post->reading_time }} min de leitura</span>
                                    </div>
                                </div>
                                @if($post->cover_image)
                                    <div class="hidden sm:block flex-shrink-0">
                                        <img src="{{ $post->cover_image }}" alt="" class="w-32 h-32 object-cover rounded">
                                    </div>
                                @endif
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        @endif

        @if(config('blog.cta_view'))
            <div class="mt-20">
                @includeIf(config('blog.cta_view'))
            </div>
        @endif
    </div>
@endsection
