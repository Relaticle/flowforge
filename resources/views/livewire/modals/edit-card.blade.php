@props(['config'])

<x-filament::modal
    id="edit-card-modal"
    :heading="__('Edit Card')"
    width="md"
>
    <form x-on:submit.prevent="submitEditForm">
        <!-- Title Field -->
        <x-filament::input.wrapper label="{{ __('Title') }}">
            <x-filament::input
                id="edit-title"
                type="text"
                x-model="formData['{{ $config['titleAttribute'] }}']"
                required
            />
        </x-filament::input.wrapper>

        <!-- Description Field (if available) -->
        @if($config['descriptionAttribute'])
            <x-filament::input.wrapper label="{{ __('Description') }}" class="mt-4">
                <x-filament::input
                    id="edit-description"
                    type="text"
                    x-model="formData['{{ $config['descriptionAttribute'] }}']"
                    multiline
                    rows="3"
                />
            </x-filament::input.wrapper>
        @endif

        <!-- Status Field -->
        <x-filament::input.wrapper label="{{ __('Status') }}" class="mt-4">
            <x-filament::input.select
                id="edit-status"
                x-model="formData['{{ $config['statusField'] }}']"
            >
                @foreach($config['statusValues'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        <!-- Additional Card Attributes -->
        @foreach($config['cardAttributes'] as $attribute)
            <x-filament::input.wrapper label="{{ __(ucfirst(str_replace('_', ' ', $attribute))) }}" class="mt-4">
                <x-filament::input
                    id="edit-{{ $attribute }}"
                    type="text"
                    x-model="formData['{{ $attribute }}']"
                />
            </x-filament::input.wrapper>
        @endforeach

        <x-slot name="footer">
            <div class="flex justify-between">
                <x-filament::button
                    type="button"
                    color="danger"
                    x-on:click="deleteCard()"
                >
                    <x-filament::icon
                        name="heroicon-m-trash"
                        class="w-4 h-4 mr-1"
                    />
                    {{ __('Delete') }}
                </x-filament::button>

                <div class="flex items-center gap-x-3">
                    <x-filament::button
                        type="button"
                        color="gray"
                        x-on:click="showEditModal = false"
                    >
                        {{ __('Cancel') }}
                    </x-filament::button>

                    <x-filament::button
                        type="submit"
                        x-on:click="submitEditForm"
                    >
                        {{ __('Update') }}
                    </x-filament::button>
                </div>
            </div>
        </x-slot>
    </form>
</x-filament::modal>
