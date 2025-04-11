@props(['singularCardLabel'])

<x-filament::modal id="create-record-modal"
                   :heading="__('Create New :cardLabel', ['cardLabel' => $singularCardLabel])"
                   :description="__('Add a new :cardLabel to the board', ['cardLabel' => strtolower($singularCardLabel)])"
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
                {{ __('Create :cardLabel', ['cardLabel' => $singularCardLabel]) }}
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
