<div class="py-6 sm:py-10 px-4 sm:px-6 lg:px-8"
     x-data
     x-on:open-preview.window="window.open($event.detail.url, '_blank', 'noopener')">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('blog.admin.index') }}"
               class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200 inline-flex items-center gap-1">
                <x-blog::icon.arrow-left class="w-4 h-4" />
                Back
            </a>
        </div>

        <div class="flex items-center gap-3 mb-6 sm:mb-8">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shrink-0">
                <x-blog::icon.pencil-square class="w-6 h-6 text-white" />
            </div>
            <div>
                <h2 class="text-2xl font-bold">{{ $post ? 'Edit post' : 'New post' }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Markdown content. Cover image and og:image via URL.
                </p>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Title</label>
                    <input type="text" wire:model.live.debounce.500ms="title"
                           class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Slug</label>
                        <input type="text" wire:model="slug"
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('slug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Category</label>
                        <select wire:model="blog_category_id"
                                class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            <option value="">Select...</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('blog_category_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                        Excerpt <span class="text-zinc-400">— shown in the listing</span>
                    </label>
                    <textarea wire:model="excerpt" rows="2"
                              class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea>
                    @error('excerpt') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-6">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Content (Markdown)</label>
                    <span class="text-xs text-zinc-500">Use `![alt](url)` for images.</span>
                </div>
                <textarea wire:model="body" rows="22"
                          class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm font-mono leading-relaxed focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                          placeholder="## Heading&#10;&#10;Your Markdown content..."></textarea>
                @error('body') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-6 space-y-4">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <h3 class="text-sm font-semibold">Publishing</h3>
                    <label class="inline-flex items-center gap-2 text-sm cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="isDraft"
                               class="rounded border-zinc-300 dark:border-zinc-700 text-emerald-600 focus:ring-emerald-500">
                        Mark as draft
                    </label>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                            Publish at <span class="text-zinc-400">— empty = draft</span>
                        </label>
                        <input type="datetime-local" wire:model.live="published_at"
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('published_at') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Reading time (min)</label>
                        <input type="number" min="1" max="120" wire:model="reading_time"
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('reading_time') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Cover image URL</label>
                        <input type="url" wire:model="cover_image" placeholder="https://..."
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('cover_image') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-6 space-y-4">
                <h3 class="text-sm font-semibold">SEO</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Meta title</label>
                        <input type="text" wire:model="meta_title"
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('meta_title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">og:image URL</label>
                        <input type="url" wire:model="og_image" placeholder="https://..."
                               class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('og_image') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Meta description</label>
                    <textarea wire:model="meta_description" rows="2"
                              class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea>
                    @error('meta_description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                        Keywords <span class="text-zinc-400">— comma-separated</span>
                    </label>
                    <input type="text" wire:model="keywords" placeholder="scheduling, management, freelancers"
                           class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 flex-wrap">
                <a href="{{ route('blog.admin.index') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    Cancel
                </a>
                <button type="button" wire:click="preview"
                        class="px-4 py-2 rounded-lg text-sm font-medium border border-emerald-500/40 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-colors inline-flex items-center gap-2"
                        title="Saves changes and opens a preview in a new tab.">
                    Save and preview
                    <span aria-hidden="true">&UpperRightArrow;</span>
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                    {{ $post ? 'Save changes' : 'Create post' }}
                </button>
            </div>
        </form>
    </div>
</div>
