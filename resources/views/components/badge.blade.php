@props(['color' => 'muted'])

@php
    $colorClasses = match ($color) {
        'brand' => 'bg-brand/10 text-brand',
        'accent' => 'bg-accent/10 text-accent',
        'gold' => 'bg-gold/10 text-gold',
        'danger' => 'bg-danger/10 text-danger',
        default => 'bg-ink-muted/10 text-ink-muted',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {$colorClasses}"]) }}>
    {{ $slot }}
</span>
