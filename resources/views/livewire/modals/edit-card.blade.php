@props(['config'])

<x-filament::modal
    id="edit-card-modal"
    :heading="__('Edit :recordLabel', ['recordLabel' => $config['recordLabel'] ?? 'Card'])"
    width="md"
>
    <form @submit.prevent="submitEditForm" class="space-y-4 relative">
        <div wire:loading.delay.shorter wire:target="updateCard, deleteCard" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 z-10 flex items-center justify-center rounded-lg backdrop-blur-sm">
            <div class="flex flex-col items-center gap-2">
                <x-filament::loading-indicator class="h-8 w-8" />
                <span wire:loading wire:target="updateCard" class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ __('Saving changes...') }}</span>
                <span wire:loading wire:target="deleteCard" class="text-sm font-medium text-danger-600 dark:text-danger-400">{{ __('Deleting :recordLabel...', ['recordLabel' => strtolower($config['recordLabel'] ?? 'card')]) }}</span>
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
                    placeholder="{{ __('Enter :recordLabel title', ['recordLabel' => strtolower($config['recordLabel'] ?? 'card')]) }}"
                    wire:loading.attr="disabled"
                    wire:target="updateCard, deleteCard"
                />
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
                    placeholder="{{ __('Enter :recordLabel description', ['recordLabel' => strtolower($config['recordLabel'] ?? 'card')]) }}"
                    wire:loading.attr="disabled"
                    wire:target="updateCard, deleteCard"
                />
            </x-filament::input.wrapper>
        </div>

        <!-- Status field -->
        <div class="space-y-2">
            <x-filament::input.wrapper>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Status') }}
                </label>

                <select
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm outline-none focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-500"
                    x-model="formData[state.statusField]"
                    wire:loading.attr="disabled"
                    wire:target="updateCard, deleteCard"
                >
                    @foreach($config['statusValues'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('The column where this :recordLabel will appear', ['recordLabel' => strtolower($config['recordLabel'] ?? 'card')]) }}
                </div>
            </x-filament::input.wrapper>
        </div>

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

                                @if($attribute == 'due_date')
                                    <x-filament::input
                                        type="date"
                                        x-model="formData.{{ $attribute }}"
                                        wire:loading.attr="disabled"
                                        wire:target="updateCard, deleteCard"
                                    />
                                @elseif($attribute == 'priority')
                                    <select
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm outline-none focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-500"
                                        x-model="formData.{{ $attribute }}"
                                        wire:loading.attr="disabled"
                                        wire:target="updateCard, deleteCard"
                                    >
                                        <option value="">{{ __('Select priority') }}</option>
                                        <option value="Low">{{ __('Low') }}</option>
                                        <option value="Medium">{{ __('Medium') }}</option>
                                        <option value="High">{{ __('High') }}</option>
                                    </select>
                                @else
                                    <x-filament::input
                                        type="text"
                                        x-model="formData.{{ $attribute }}"
                                        placeholder="{{ __('Enter') }} {{ strtolower($label) }}"
                                        wire:loading.attr="disabled"
                                        wire:target="updateCard, deleteCard"
                                    />
                                @endif
                            </x-filament::input.wrapper>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </form>

    <x-slot name="footer">
        <div class="flex items-center justify-between">
            <x-filament::button
                color="danger"
                @click="deleteCard"
                class="mr-auto"
                wire:loading.attr="disabled"
                wire:target="updateCard, deleteCard"
            >
                <span wire:loading.remove wire:target="deleteCard">{{ __('Delete') }}</span>
                <span wire:loading wire:target="deleteCard" class="flex items-center gap-1">
                    <x-filament::loading-indicator class="h-4 w-4" />
                    {{ __('Deleting...') }}
                </span>
            </x-filament::button>

            <div class="flex gap-x-3">
                <x-filament::button
                    color="gray"
                    @click="$dispatch('close-modal', { id: 'edit-card-modal' })"
                    wire:loading.attr="disabled"
                    wire:target="updateCard, deleteCard"
                >
                    {{ __('Cancel') }}
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    @click="submitEditForm"
                    x-bind:disabled="!formData.title"
                    x-bind:class="{ 'opacity-70 cursor-not-allowed': !formData.title }"
                    wire:loading.attr="disabled"
                    wire:target="updateCard, deleteCard"
                >
                    <span wire:loading.remove wire:target="updateCard">{{ __('Save Changes') }}</span>
                    <span wire:loading wire:target="updateCard" class="flex items-center gap-1">
                        <x-filament::loading-indicator class="h-4 w-4" />
                        {{ __('Saving...') }}
                    </span>
                </x-filament::button>
            </div>
        </div>
    </x-slot>
</x-filament::modal>
