<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-accent-dark dark:bg-accent text-white dark:text-slate-900 rounded-full font-display font-semibold text-sm shadow-sm hover:shadow-md motion-safe:hover:-translate-y-0.5 motion-safe:active:translate-y-0 motion-safe:transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:opacity-40 disabled:pointer-events-none disabled:hover:translate-y-0 disabled:shadow-none']) }}>
    {{ $slot }}
</button>
