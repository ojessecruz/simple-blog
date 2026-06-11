@props(['class' => 'h-2.5 w-24'])

{{-- Hand-drawn underline accent. Inherits its color from `text-*` classes. --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 220 12" fill="none" preserveAspectRatio="none" aria-hidden="true">
    <path d="M3 8.5 C 32 3, 58 10.5, 92 6.5 S 158 2.5, 217 7.5" stroke="currentColor" stroke-width="6" stroke-linecap="round" />
</svg>
