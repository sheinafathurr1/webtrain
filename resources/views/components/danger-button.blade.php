<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-danger text-white rounded-full font-display font-semibold text-sm shadow-sm hover:opacity-90 motion-safe:transition-opacity duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger disabled:opacity-40 disabled:pointer-events-none']) }}>
    {{ $slot }}
</button>
