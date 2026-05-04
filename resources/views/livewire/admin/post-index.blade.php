<div class="py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        @if(config('blog.admin_back_url'))
            <div class="flex items-center gap-3 mb-6">
                <a href="{{ config('blog.admin_back_url') }}"
                   class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 inline-flex items-center gap-1">
                    <x-blog::icon.arrow-left class="w-4 h-4" />
                    {{ __('blog::messages.back') }}
                </a>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shrink-0">
                    <x-blog::icon.pencil-square class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('blog::messages.blog') }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('blog::messages.admin_subtitle') }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('blog.admin.categories') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    {{ __('blog::messages.categories') }}
                </a>
                <a href="{{ route('blog.admin.create') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                    {{ __('blog::messages.new_post') }}
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 mb-6">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.status') }}</label>
                    <select wire:model.live="statusFilter"
                            class="block rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="">{{ __('blog::messages.all') }}</option>
                        <option value="published">{{ __('blog::messages.published') }}</option>
                        <option value="draft">{{ __('blog::messages.draft') }}</option>
                        <option value="scheduled">{{ __('blog::messages.scheduled') }}</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.search') }}</label>
                    <input wire:model.live.debounce.400ms="search" type="text"
                           placeholder="{{ __('blog::messages.post_title_placeholder') }}"
                           class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <div class="space-y-3">
            @forelse ($posts as $post)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    {{ $post->category->name }}
                                </span>
                                @if ($post->isPublished())
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                        {{ __('blog::messages.published') }}
                                    </span>
                                @elseif ($post->published_at)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                        {{ __('blog::messages.scheduled') }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-500/10 text-zinc-600 dark:text-zinc-400">
                                        {{ __('blog::messages.draft') }}
                                    </span>
                                @endif
                                <span class="text-xs text-zinc-500">{{ __('blog::messages.views_count', ['count' => $post->views_count]) }}</span>
                            </div>
                            <h3 class="text-base font-semibold break-words text-zinc-900 dark:text-zinc-100">{{ $post->title }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 break-words line-clamp-2">{{ $post->excerpt }}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-3 text-xs text-zinc-500">
                                <span>/{{ $post->slug }}</span>
                                @if ($post->published_at)
                                    <span>•</span>
                                    <span>{{ $post->published_at->format('m/d/Y H:i') }}</span>
                                @endif
                                <span>•</span>
                                <span>{{ $post->reading_time }} {{ __('blog::messages.min') }}</span>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                            @if ($post->isPublished())
                                <a href="{{ $post->url() }}" target="_blank"
                                   class="px-3 py-1.5 text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                    {{ __('blog::messages.view') }}
                                </a>
                            @endif
                            <a href="{{ route('blog.admin.edit', $post) }}"
                               class="px-3 py-1.5 text-xs rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                                {{ __('blog::messages.edit') }}
                            </a>
                            <button type="button"
                                    wire:click="delete({{ $post->id }})"
                                    wire:confirm="{{ __('blog::messages.delete_post_confirm') }}"
                                    class="px-3 py-1.5 text-xs rounded-lg border border-red-300 dark:border-red-700/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors">
                                {{ __('blog::messages.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-8 text-center text-zinc-500 dark:text-zinc-400">
                    {{ __('blog::messages.no_posts') }}
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    </div>
</div>
