<div class="py-6 sm:py-10 px-4 sm:px-6 lg:px-8"
     x-data
     x-on:open-preview.window="window.open($event.detail.url, '_blank', 'noopener')">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('blog.admin.index') }}"
               class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 inline-flex items-center gap-1">
                <x-blog::icon.arrow-left class="w-4 h-4" />
                {{ __('blog::messages.back') }}
            </a>
        </div>

        <div class="flex items-center gap-3 mb-6 sm:mb-8">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shrink-0">
                <x-blog::icon.pencil-square class="w-6 h-6 text-white" />
            </div>
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $post ? __('blog::messages.edit_post') : __('blog::messages.new_post') }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('blog::messages.post_form_subtitle') }}
                </p>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.title') }}</label>
                    <input type="text" wire:model.live.debounce.500ms="title"
                           class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.slug') }}</label>
                        <input type="text" wire:model="slug"
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('slug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.category') }}</label>
                        <select wire:model="blog_category_id"
                                class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            <option value="">{{ __('blog::messages.select_placeholder') }}</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('blog_category_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                        {{ __('blog::messages.excerpt') }} <span class="text-zinc-400 dark:text-zinc-500">— {{ __('blog::messages.excerpt_hint') }}</span>
                    </label>
                    <textarea wire:model="excerpt" rows="2"
                              class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea>
                    @error('excerpt') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                    <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('blog::messages.content_markdown') }}</label>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-zinc-500 hidden sm:inline">{{ __('blog::messages.images_hint') }}</span>
                        <div class="inline-flex rounded-md border border-zinc-200 dark:border-zinc-700 p-0.5">
                            <button type="button" wire:click="$set('splitView', false)"
                                    title="{{ __('blog::messages.edit_only') }}"
                                    aria-label="{{ __('blog::messages.edit_only') }}"
                                    class="p-1.5 rounded {{ ! $splitView ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100' }} transition-colors">
                                <x-blog::icon.code-bracket class="w-4 h-4" />
                            </button>
                            <button type="button" wire:click="$set('splitView', true)"
                                    title="{{ __('blog::messages.split_view') }}"
                                    aria-label="{{ __('blog::messages.split_view') }}"
                                    class="p-1.5 rounded {{ $splitView ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100' }} transition-colors">
                                <x-blog::icon.view-columns class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
                <div class="grid gap-4 {{ $splitView ? 'lg:grid-cols-2' : 'grid-cols-1' }}">
                    <textarea wire:model.live.debounce.500ms="body" rows="22"
                              class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm font-mono leading-relaxed focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                              placeholder="## Heading&#10;&#10;Your Markdown content..."></textarea>
                    @if($splitView)
                        <div class="rounded-md border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950 px-4 py-3 overflow-auto blog-content" style="max-height: 33rem;">
                            @if(trim($body) === '')
                                <p class="text-zinc-400 dark:text-zinc-600 text-sm italic">{{ __('blog::messages.preview') }}</p>
                            @else
                                {!! \Illuminate\Support\Str::markdown($body, config('blog.markdown', ['html_input' => 'escape', 'allow_unsafe_links' => false])) !!}
                            @endif
                        </div>
                    @endif
                </div>
                @error('body') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-6 space-y-4">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('blog::messages.publishing') }}</h3>
                    <label class="inline-flex items-center gap-2 text-sm cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="isDraft"
                               class="rounded border-zinc-300 dark:border-zinc-700 text-emerald-600 focus:ring-emerald-500">
                        {{ __('blog::messages.mark_as_draft') }}
                    </label>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                            {{ __('blog::messages.publish_at') }} <span class="text-zinc-400 dark:text-zinc-500">— {{ __('blog::messages.publish_at_hint') }}</span>
                        </label>
                        <input type="datetime-local" wire:model.live="published_at"
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('published_at') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.reading_time') }}</label>
                        <input type="number" min="1" max="120" wire:model="reading_time"
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('reading_time') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.cover_image_url') }}</label>
                        <input type="url" wire:model="cover_image" placeholder="https://..."
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('cover_image') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-6 space-y-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('blog::messages.seo') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.meta_title') }}</label>
                        <input type="text" wire:model="meta_title"
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('meta_title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.og_image_url') }}</label>
                        <input type="url" wire:model="og_image" placeholder="https://..."
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('og_image') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.meta_description') }}</label>
                    <textarea wire:model="meta_description" rows="2"
                              class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea>
                    @error('meta_description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                        {{ __('blog::messages.keywords') }} <span class="text-zinc-400 dark:text-zinc-500">— {{ __('blog::messages.keywords_hint') }}</span>
                    </label>
                    <input type="text" wire:model="keywords" placeholder="scheduling, management, freelancers"
                           class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 flex-wrap">
                <a href="{{ route('blog.admin.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    {{ __('blog::messages.cancel') }}
                </a>
                <button type="button" wire:click="preview"
                        class="px-4 py-2 rounded-lg text-sm font-medium border border-emerald-500/40 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-colors inline-flex items-center gap-2"
                        title="Saves changes and opens a preview in a new tab.">
                    {{ __('blog::messages.save_and_preview') }}
                    <span aria-hidden="true">&UpperRightArrow;</span>
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                    {{ $post ? __('blog::messages.save_changes') : __('blog::messages.create_post') }}
                </button>
            </div>
        </form>
    </div>

    <style>
        .blog-content { color: #27272a; font-size: 0.9375rem; line-height: 1.7; }
        .blog-content > * + * { margin-top: 1em; }
        .blog-content h1 { font-size: 1.5rem; font-weight: 700; line-height: 1.3; margin-top: 1.4em; margin-bottom: 0.5em; color: #18181b; letter-spacing: -0.01em; }
        .blog-content h2 { font-size: 1.25rem; font-weight: 600; line-height: 1.3; margin-top: 1.4em; margin-bottom: 0.4em; color: #18181b; letter-spacing: -0.01em; }
        .blog-content h3 { font-size: 1.0625rem; font-weight: 600; line-height: 1.35; margin-top: 1.2em; margin-bottom: 0.3em; color: #18181b; }
        .blog-content h2 + p, .blog-content h3 + p { margin-top: 0; }
        .blog-content strong { color: #18181b; font-weight: 600; }
        .blog-content a { color: #059669; text-decoration: underline; text-underline-offset: 3px; }
        .blog-content a:hover { color: #047857; }
        .blog-content ul, .blog-content ol { padding-left: 1.5rem; }
        .blog-content ul { list-style: disc; }
        .blog-content ol { list-style: decimal; }
        .blog-content li + li { margin-top: 0.3em; }
        .blog-content blockquote { border-left: 3px solid #d4d4d8; padding-left: 0.875rem; color: #52525b; font-style: italic; }
        .blog-content code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #f4f4f5; color: #18181b; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875em; }
        .blog-content pre { background: #fafafa; border: 1px solid #e4e4e7; border-radius: 0.375rem; padding: 0.75rem 0.875rem; overflow-x: auto; font-size: 0.8125rem; line-height: 1.55; }
        .blog-content pre code { background: transparent; padding: 0; font-size: inherit; }
        .blog-content img { max-width: 100%; height: auto; border-radius: 0.375rem; }
        .blog-content table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .blog-content th, .blog-content td { padding: 0.4rem 0.6rem; border-bottom: 1px solid #e4e4e7; text-align: left; }
        .blog-content th { font-weight: 600; color: #18181b; background: #fafafa; }
        .blog-content hr { border: 0; border-top: 1px solid #e4e4e7; margin: 1.5rem 0; }

        .dark .blog-content { color: #d4d4d8; }
        .dark .blog-content h1,
        .dark .blog-content h2,
        .dark .blog-content h3,
        .dark .blog-content strong { color: #fafafa; }
        .dark .blog-content a { color: #34d399; }
        .dark .blog-content a:hover { color: #6ee7b7; }
        .dark .blog-content blockquote { border-left-color: #3f3f46; color: #a1a1aa; }
        .dark .blog-content code { background: #27272a; color: #f4f4f5; }
        .dark .blog-content pre { background: #09090b; border-color: #27272a; }
        .dark .blog-content th { color: #fafafa; background: #09090b; }
        .dark .blog-content th, .dark .blog-content td { border-bottom-color: #27272a; }
        .dark .blog-content hr { border-top-color: #3f3f46; }
    </style>
</div>
