@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm text-ink-primary']) }}>
        {{ $status }}
    </div>
@endif
