@props(['class' => 'w-5 h-5'])

<svg {{ $attributes->merge(['class' => $class]) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
</svg>
