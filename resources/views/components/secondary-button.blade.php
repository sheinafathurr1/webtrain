<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-ghost inline-flex items-center px-4 py-2 bg-transparent border border-border-interactive rounded font-medium text-sm text-ink-primary hover:border-ink-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-primary disabled:opacity-40 disabled:pointer-events-none transition-colors duration-150']) }}>
    {{ $slot }}
</button>
