@props(['singularCardLabel', 'permissions'])

<x-filament::modal id="edit-record-modal" :heading="__('Edit :cardLabel', ['cardLabel' => $singularCardLabel])">
    {{ $this->editRecordForm }}

    <x-slot name="footer">
        <div class="flex items-center justify-between">
            @if($this->permissions['canDelete'])
                <x-filament::button
                    color="danger"
                    wire:click="deleteRecord"
                >
                    {{ __('Delete') }}
                </x-filament::button>
            @endif



            <div class="flex gap-x-3">
                <x-filament::button
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'edit-record-modal' })"
                >
                    {{ __('Cancel') }}
                </x-filament::button>

                <x-filament::button
                    wire:click="updateRecord"
                >
                    {{ __('Save Changes') }}
                </x-filament::button>
            </div>
        </div>
    </x-slot>
</x-filament::modal>
