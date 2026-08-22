@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-2 border-ink-primary text-start text-base font-medium text-ink-primary bg-canvas focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ink-primary transition-colors duration-150'
            : 'block w-full ps-3 pe-4 py-2 border-l-2 border-transparent text-start text-base font-medium text-ink-muted hover:text-ink-primary hover:bg-canvas hover:border-border-interactive focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ink-primary transition-colors duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
