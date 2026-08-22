@props(['muted' => false])

<span {{ $attributes->merge(['class' => 'font-mono font-semibold tracking-tight inline-flex items-baseline']) }}>
    <span class="{{ $muted ? 'text-ink-muted' : 'text-ink-primary' }}">webtrain</span><span class="text-ink-muted">_</span>
</span>
