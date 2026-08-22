@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-surface border-border-interactive text-ink-primary placeholder:text-ink-muted focus:border-ink-primary focus:ring-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-primary rounded disabled:opacity-40']) }}>
