@extends(config('blog.layouts.public'))

@push('head')
    <title>@if(isset($currentCategory)){{ $currentCategory->name }} — @endif Blog</title>
    @if(isset($currentCategory) && $currentCategory->description)
        <meta name="description" content="{{ $currentCategory->description }}">
    @endif
@endpush

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
        <header class="mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold mb-2">
                @if(isset($currentCategory))
                    {{ $currentCategory->name }}
                @else
                    Blog
                @endif
            </h1>
            <p class="text-base text-zinc-600 dark:text-zinc-400">
                @if(isset($currentCategory) && $currentCategory->description)
                    {{ $currentCategory->description }}
                @else
                    Latest posts.
                @endif
            </p>
        </header>

        @if($categories->isNotEmpty())
            <nav class="flex flex-wrap gap-x-5 gap-y-2 mb-8 pb-5 border-b border-zinc-200 dark:border-zinc-800 text-sm">
                <a href="{{ route('blog.index') }}"
                   class="{{ ! isset($currentCategory) ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }} transition-colors">
                    All
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
            <div class="text-center py-12 text-zinc-500 dark:text-zinc-400 text-sm">
                <p>New content coming soon.</p>
            </div>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($posts as $post)
                    <li class="py-6 first:pt-0">
                        <a href="{{ $post->url() }}" class="group flex gap-5 sm:gap-6">
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                    {{ $post->category->name }}
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold mt-1 mb-1 leading-snug group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                    {{ $post->title }}
                                </h2>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2 line-clamp-2">
                                    {{ $post->excerpt }}
                                </p>
                                <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-500">
                                    <span>{{ $post->authorName() }}</span>
                                    <span aria-hidden="true">&middot;</span>
                                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                                        {{ $post->published_at->translatedFormat('M j, Y') }}
                                    </time>
                                    <span aria-hidden="true">&middot;</span>
                                    <span>{{ $post->reading_time }} min read</span>
                                </div>
                            </div>
                            @if($post->cover_image)
                                <div class="hidden sm:block flex-shrink-0">
                                    <img src="{{ $post->cover_image }}" alt="" class="w-24 h-24 object-cover rounded">
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
