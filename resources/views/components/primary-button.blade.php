<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-ink-primary text-canvas rounded font-medium text-sm hover:opacity-85 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-primary disabled:opacity-40 disabled:pointer-events-none transition-opacity duration-150']) }}>
    {{ $slot }}
</button>
