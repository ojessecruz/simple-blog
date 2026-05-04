<div class="py-6 sm:py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
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
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('blog::messages.blog_categories') }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('blog::messages.categories_subtitle') }}</p>
            </div>
        </div>

        <form wire:submit="save" class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 sm:p-5 mb-6 space-y-4">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $editingId ? __('blog::messages.edit_category') : __('blog::messages.new_category') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.name') }}</label>
                    <input type="text" wire:model.live.debounce.500ms="name"
                           class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.slug') }}</label>
                    <input type="text" wire:model="slug"
                           class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    @error('slug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('blog::messages.description') }}</label>
                <input type="text" wire:model="description"
                       class="block w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center justify-end gap-2">
                @if ($editingId)
                    <button type="button" wire:click="cancel"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        {{ __('blog::messages.cancel_edit') }}
                    </button>
                @endif
                <button type="submit"
                        class="px-4 py-1.5 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                    {{ $editingId ? __('blog::messages.save') : __('blog::messages.create') }}
                </button>
            </div>
        </form>

        <div class="space-y-2">
            @forelse ($categories as $category)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $category->name }}</h3>
                            <span class="text-xs text-zinc-500">/{{ $category->slug }}</span>
                        </div>
                        @if ($category->description)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $category->description }}</p>
                        @endif
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ __('blog::messages.posts_count', ['count' => $category->posts_count]) }}</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        <button type="button" wire:click="edit({{ $category->id }})"
                                class="px-3 py-1.5 text-xs rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                            {{ __('blog::messages.edit') }}
                        </button>
                        <button type="button"
                                wire:click="delete({{ $category->id }})"
                                wire:confirm="{{ __('blog::messages.delete_category_confirm') }}"
                                class="px-3 py-1.5 text-xs rounded-lg border border-red-300 dark:border-red-700/50 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors">
                            {{ __('blog::messages.delete') }}
                        </button>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-8 text-center text-zinc-500 dark:text-zinc-400">
                    {{ __('blog::messages.no_categories') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
