@props(['config'])

<x-filament::modal id="create-card-modal" :heading="__('Create New :cardLabel', ['cardLabel' => $config->cardLabel ?? 'Record'])"
:description="__('Add a new :cardLabel to the board', ['cardLabel' => strtolower($config->cardLabel ?? 'record')])"
>

    {{ $this->createRecordForm }}

    <x-slot name="footer">
        <div class="flex justify-end gap-x-3">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'create-card-modal' })"
            >
                {{ __('Cancel') }}
            </x-filament::button>

            <x-filament::button
                wire:click="createRecord"
            >
                {{ __('Create :cardLabel', ['cardLabel' => $config->cardLabel ?? 'Record']) }}
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
