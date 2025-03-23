@props(['config'])

<x-filament::modal
    id="create-card-modal"
    :heading="__('Create New Card')"
    :description="__('Add a new card to the board')"
    icon="heroicon-o-plus-circle"
    icon-color="primary"
    width="md"
>
    <form @submit.prevent="submitCreateForm" class="space-y-4">
        <div wire:loading.delay.shorter wire:target="createCard" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 z-10 flex items-center justify-center rounded-lg backdrop-blur-sm">
            <div class="flex flex-col items-center gap-2">
                <x-filament::loading-indicator class="h-8 w-8" />
                <span class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ __('Creating card...') }}</span>
            </div>
        </div>

        <!-- Title field -->
        <div class="space-y-2">
            <x-filament::input.wrapper>
                <label class="inline-flex items-center space-x-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>{{ __('Title') }}</span>
                    <span class="text-danger-500">*</span>
                </label>

                <x-filament::input
                    type="text"
                    x-model="formData.title"
                    required
                    autofocus
                    placeholder="{{ __('Enter card title') }}"
                    wire:loading.attr="disabled"
                    wire:target="createCard"
                />

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('A descriptive title for this card') }}
                </div>
            </x-filament::input.wrapper>
        </div>

        <!-- Description field -->
        <div class="space-y-2">
            <x-filament::input.wrapper>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Description') }}
                </label>

                <x-filament::input
                    type="textarea"
                    x-model="formData.description"
                    placeholder="{{ __('Enter card description') }}"
                    wire:loading.attr="disabled"
                    wire:target="createCard"
                />

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Additional details about this card') }}
                </div>
            </x-filament::input.wrapper>
        </div>

        <!-- Status field (hidden) -->
        <input type="hidden" x-model="formData[state.statusField]" />

        <!-- Additional attributes -->
        @if(!empty($config['cardAttributes']))
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('Additional Details') }}</h3>

                <div class="space-y-4">
                    @foreach($config['cardAttributes'] as $attribute => $label)
                        <div class="space-y-2">
                            <x-filament::input.wrapper>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $label }}
                                </label>

                                <x-filament::input
                                    type="text"
                                    x-model="formData.{{ $attribute }}"
                                    placeholder="{{ __('Enter') }} {{ strtolower($label) }}"
                                    wire:loading.attr="disabled"
                                    wire:target="createCard"
                                />
                            </x-filament::input.wrapper>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </form>

    <x-slot name="footer">
        <div class="flex justify-end gap-x-3">
            <x-filament::button
                color="gray"
                @click="$dispatch('close-modal', { id: 'create-card-modal' })"
                wire:loading.attr="disabled"
                wire:target="createCard"
            >
                {{ __('Cancel') }}
            </x-filament::button>

            <x-filament::button
                type="submit"
                @click="submitCreateForm"
                x-bind:disabled="!formData.title"
                x-bind:class="{ 'opacity-70 cursor-not-allowed': !formData.title }"
                wire:loading.attr="disabled"
                wire:target="createCard"
            >
                <span wire:loading.remove wire:target="createCard">{{ __('Create Card') }}</span>
                <span wire:loading wire:target="createCard" class="flex items-center gap-1">
                    <x-filament::loading-indicator class="h-4 w-4" />
                    {{ __('Creating...') }}
                </span>
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
