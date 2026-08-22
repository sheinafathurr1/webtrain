<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-transparent border-2 border-brand text-brand rounded-full font-display font-semibold text-sm motion-safe:transition-colors duration-200 hover:bg-brand hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-40 disabled:pointer-events-none']) }}>
    {{ $slot }}
</button>
