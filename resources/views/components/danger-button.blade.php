<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-transparent border border-danger rounded font-medium text-sm text-danger hover:bg-danger-surface focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger disabled:opacity-40 disabled:pointer-events-none transition-colors duration-150']) }}>
    {{ $slot }}
</button>
