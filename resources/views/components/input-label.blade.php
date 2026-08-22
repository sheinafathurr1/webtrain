@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-ink-secondary']) }}>
    {{ $value ?? $slot }}
</label>
