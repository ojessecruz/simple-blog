@props(['items' => [], 'nested' => false])

{{-- Items come from TableOfContents::render($post); `nested` renders just the list (used for the recursion) --}}
@if($items !== [])
    @if($nested)
        <ol {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
            @foreach($items as $item)
                <li>
                    <a href="#{{ $item['id'] }}"
                       class="text-zinc-700 underline-offset-4 transition hover:text-zinc-900 hover:underline hover:decoration-emerald-500 hover:decoration-2 dark:text-zinc-300 dark:hover:text-zinc-100">
                        {{ $item['text'] }}
                    </a>
                    <x-blog::toc :items="$item['children']" nested class="mt-1.5 list-[circle] pl-5 text-sm marker:text-emerald-500" />
                </li>
            @endforeach
        </ol>
    @else
        <nav data-blog-toc aria-labelledby="blog-toc-title" {{ $attributes->merge(['class' => 'rounded-xl border-2 border-zinc-200 px-5 py-4 dark:border-zinc-700']) }}>
            <details open class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                    <span id="blog-toc-title" class="font-mono text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
                        {{ __('blog::messages.toc') }}
                    </span>
                    <x-blog::icon.chevron-down class="h-4 w-4 text-zinc-400 transition-transform group-open:rotate-180" />
                </summary>

                <x-blog::toc :items="$items" nested class="mt-3 list-decimal pl-5 text-[15px] marker:font-mono marker:text-xs marker:text-emerald-600 dark:marker:text-emerald-400" />
            </details>
        </nav>
    @endif
@endif
