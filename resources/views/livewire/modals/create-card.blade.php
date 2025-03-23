@props(['config'])

<x-filament::modal id="create-card-modal" :heading="__('Create New :recordLabel', ['recordLabel' => $config['recordLabel'] ?? 'Card'])">
    <x-filament::modal.description>
        {{ __('Add a new :recordLabel to the board', ['recordLabel' => strtolower($config['recordLabel'] ?? 'card')]) }}
    </x-filament::modal.description>

    {{ $this->createForm }}

    <x-slot name="footer">
        <div class="flex justify-end gap-x-3">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'create-card-modal' })"
            >
                {{ __('Cancel') }}
            </x-filament::button>

            <x-filament::button
                wire:click="createCard"
            >
                {{ __('Create :recordLabel', ['recordLabel' => $config['recordLabel'] ?? 'Card']) }}
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
