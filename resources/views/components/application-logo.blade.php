@props(['muted' => false])

<span {{ $attributes->merge(['class' => 'font-display font-extrabold tracking-tight inline-flex items-baseline']) }}>
    <span class="{{ $muted ? 'text-ink-muted' : 'text-ink-primary' }}">web</span><span class="text-accent">train</span>
</span>
