@props(['title', 'message', 'action' => 'delete'])

<div x-show="$wire.confirmingDeleteId !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" wire:click="confirmingDeleteId = null"></div>

    <div class="relative bg-surface border border-border rounded-2xl shadow-2xl max-w-sm w-full p-6 text-ink-primary">
        <h3 class="font-display text-lg font-bold mb-2">{{ $title }}</h3>
        <p class="text-sm text-ink-secondary mb-6">{{ $message }}</p>

        <div class="flex justify-end gap-3">
            <x-secondary-button type="button" wire:click="confirmingDeleteId = null">{{ __('Batal') }}</x-secondary-button>
            <x-danger-button type="button" wire:click="{{ $action }}">{{ __('Hapus') }}</x-danger-button>
        </div>
    </div>
</div>
