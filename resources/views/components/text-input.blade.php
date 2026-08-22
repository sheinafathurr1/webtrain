@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-surface border-2 border-border text-ink-primary placeholder:text-ink-muted focus:border-brand focus:ring-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand rounded-xl disabled:opacity-40']) }}>
