@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm text-brand font-semibold']) }}>
        {{ $status }}
    </div>
@endif
