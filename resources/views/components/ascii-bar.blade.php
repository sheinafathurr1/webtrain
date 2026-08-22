@props(['percent' => 0, 'width' => 10])

@php
    $percent = max(0, min(100, (int) round($percent)));
    $filled = (int) round($percent / 100 * $width);
    $empty = $width - $filled;
@endphp

<span {{ $attributes->merge(['class' => 'ascii-bar']) }}>
    <span class="ascii-bar-track">[<span class="ascii-bar-fill">{{ str_repeat('█', $filled) }}</span>{{ str_repeat('░', $empty) }}]</span>
    <span class="ascii-bar-pct">{{ $percent }}%</span>
</span>
