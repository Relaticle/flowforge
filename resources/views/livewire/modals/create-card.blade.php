@props(['config'])

<x-filament::modal
    id="create-card-modal"
    :heading="__('Create New Card')"
    width="md"
>
    <form x-on:submit="submitCreateForm">
        <!-- Title Field -->
        <x-filament::input.wrapper label="{{ __('Title') }}">
            <x-filament::input
                id="title"
                type="text"
                x-model="formData['{{ $config['titleAttribute'] }}']"
                required
            />
        </x-filament::input.wrapper>
        
        <!-- Description Field (if available) -->
        @if($config['descriptionAttribute'])
            <x-filament::input.wrapper label="{{ __('Description') }}" class="mt-4">
                <x-filament::input
                    id="description"
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
                id="status"
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
                    id="{{ $attribute }}"
                    type="text"
                    x-model="formData['{{ $attribute }}']"
                />
            </x-filament::input.wrapper>
        @endforeach

        <x-slot name="footer">
            <div class="flex justify-end gap-x-3">
                <x-filament::button
                    type="button"
                    color="gray"
                    x-on:click="showCreateModal = false"
                >
                    {{ __('Cancel') }}
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    x-on:click="submitCreateForm"
                >
                    {{ __('Create') }}
                </x-filament::button>
            </div>
        </x-slot>
    </form>
</x-filament::modal>
