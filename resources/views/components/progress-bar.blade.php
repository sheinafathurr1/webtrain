@props(['percent' => 0])

@php
    $percent = max(0, min(100, (int) round($percent)));
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <div class="progress-track flex-1">
        <div class="progress-fill" style="width: {{ $percent }}%"></div>
    </div>
    <span class="font-display font-bold text-sm text-ink-primary tabular-nums w-11 text-right">{{ $percent }}%</span>
</div>
