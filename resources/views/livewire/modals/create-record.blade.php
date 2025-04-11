@props(['config'])

<x-filament::modal id="create-record-modal"
                   :heading="__('Create New :cardLabel', ['cardLabel' => $config->getCardLabel() ?? 'Record'])"
                   :description="__('Add a new :cardLabel to the board', ['cardLabel' => strtolower($config->getCardLabel() ?? 'record')])"
>

    {{ $this->createRecordForm }}

    <x-slot name="footer">
        <div class="flex justify-end gap-x-3">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'create-record-modal' })"
            >
                {{ __('Cancel') }}
            </x-filament::button>

            <x-filament::button
                wire:click="createRecord"
            >
                {{ __('Create :cardLabel', ['cardLabel' => $config->getCardLabel() ?? 'Record']) }}
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
