@props(['post'])

@php
    $links = \Jessecruz\SimpleBlog\Support\Share::links($post);
    $icons = [
        'whatsapp' => 'blog::icon.whatsapp',
        'x' => 'blog::icon.x-twitter',
        'linkedin' => 'blog::icon.linkedin',
        'facebook' => 'blog::icon.facebook',
        'telegram' => 'blog::icon.telegram',
        'email' => 'blog::icon.envelope',
        'copy' => 'blog::icon.link',
        'native' => 'blog::icon.share',
    ];
    $buttonClass = 'rounded-lg border-2 border-zinc-200 p-2 text-zinc-500 transition hover:-rotate-6 hover:border-emerald-500 hover:text-emerald-600 dark:border-zinc-700 dark:hover:border-emerald-500 dark:hover:text-emerald-400';
@endphp

{{-- Drafts and scheduled posts (admin preview) have no public URL to share yet --}}
@if($links !== [] && $post->isPublished())
    <div data-blog-share role="group" aria-label="{{ __('blog::messages.share') }}" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
        <span aria-hidden="true" class="mr-1 font-mono text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
            {{ __('blog::messages.share') }}
        </span>

        @foreach($links as $link)
            @if($link['network'] === 'copy')
                <button type="button" data-blog-share-copy data-url="{{ $post->url() }}" data-copied="{{ __('blog::messages.link_copied') }}"
                        class="{{ $buttonClass }}" title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}">
                    <x-blog::icon.link class="h-4 w-4" data-blog-share-copy-icon />
                    {{-- SVG elements ignore the hidden attribute, so the icons toggle via inline display --}}
                    <x-blog::icon.check class="h-4 w-4" data-blog-share-copied-icon style="display: none" />
                </button>
            @elseif($link['network'] === 'native')
                {{-- Revealed by the script below only where navigator.share exists (mostly mobile) --}}
                <button type="button" hidden data-blog-share-native data-url="{{ $post->url() }}" data-title="{{ $post->title }}"
                        class="{{ $buttonClass }}" title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}">
                    <x-blog::icon.share class="h-4 w-4" />
                </button>
            @else
                <a href="{{ $link['href'] }}" data-blog-share-link="{{ $link['network'] }}"
                   @if($link['network'] !== 'email') target="_blank" rel="noopener noreferrer" @endif
                   class="{{ $buttonClass }}" title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}">
                    <x-dynamic-component :component="$icons[$link['network']]" class="h-4 w-4" />
                </a>
            @endif
        @endforeach

        <span data-blog-share-status role="status" class="sr-only"></span>
    </div>

    @once
        <script @if($nonce = \Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ $nonce }}" @endif>
            (() => {
                const copyText = (text) => {
                    if (navigator.clipboard && window.isSecureContext) {
                        return navigator.clipboard.writeText(text);
                    }

                    // Fallback for plain-HTTP origins, where the Clipboard API is unavailable.
                    return new Promise((resolve, reject) => {
                        const field = document.createElement('textarea');
                        field.value = text;
                        field.setAttribute('readonly', '');
                        field.style.position = 'fixed';
                        field.style.opacity = '0';
                        document.body.appendChild(field);
                        field.select();
                        const copied = document.execCommand('copy');
                        field.remove();
                        copied ? resolve() : reject();
                    });
                };

                const init = () => document.querySelectorAll('[data-blog-share]:not([data-blog-share-ready])').forEach((root) => {
                    root.setAttribute('data-blog-share-ready', '');
                    const status = root.querySelector('[data-blog-share-status]');

                    const native = root.querySelector('[data-blog-share-native]');
                    if (native && typeof navigator.share === 'function') {
                        native.hidden = false;
                        native.addEventListener('click', () => {
                            const { title, url } = native.dataset;
                            // Rejects when the user dismisses the share sheet; nothing to do then.
                            navigator.share({ title, text: title, url }).catch(() => {});
                        });
                    }

                    const copy = root.querySelector('[data-blog-share-copy]');
                    if (copy) {
                        const linkIcon = copy.querySelector('[data-blog-share-copy-icon]');
                        const checkIcon = copy.querySelector('[data-blog-share-copied-icon]');
                        let timer;

                        copy.addEventListener('click', () => copyText(copy.dataset.url).then(() => {
                            linkIcon.style.display = 'none';
                            checkIcon.style.display = '';
                            status.textContent = copy.dataset.copied;
                            clearTimeout(timer);
                            timer = setTimeout(() => {
                                linkIcon.style.display = '';
                                checkIcon.style.display = 'none';
                                status.textContent = '';
                            }, 2000);
                        }).catch(() => {}));
                    }
                });

                document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
            })();
        </script>
    @endonce
@endif
