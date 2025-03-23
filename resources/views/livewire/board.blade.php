@props(['columns', 'config'])

<div
    class="w-full h-full flex flex-col relative"
    x-load
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('flowforge', package: 'relaticle/flowforge'))]"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
        state: {
            columns: @js($columns),
            statusField: '{{ $config['statusField'] }}',
            recordLabel: '{{ $config['recordLabel'] ?? 'Card' }}',
            pluralRecordLabel: '{{ $config['pluralRecordLabel'] ?? 'Cards' }}'
        }
    })"
>
    <!-- Loading overlay for board operations -->
    <div wire:loading.delay.longer wire:target="updateColumnCards"
         class="absolute inset-0 bg-white/40 dark:bg-gray-900/40 z-10 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 flex items-center gap-3">
            <x-filament::loading-indicator class="h-6 w-6 text-primary-500" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Updating board...') }}</span>
        </div>
    </div>

    <!-- Board Header with filters or actions could go here -->
    <div class="flex items-center justify-between mb-4 px-3">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ $config['pluralRecordLabel'] ?? 'Cards' }} Board</h2>

        <!-- Optional board-level actions could go here -->
    </div>

    <!-- Board Content -->
    <div class="flex-1 overflow-hidden">
        <div class="flex flex-row h-full overflow-x-auto overflow-y-hidden py-4 px-2 gap-5 kanban-board pb-4">
            @foreach($columns as $columnId => $column)
                <div class="kanban-column w-[300px] min-w-[300px] flex-shrink-0 bg-kanban-column-bg dark:bg-kanban-column-bg border border-kanban-column-border dark:border-gray-700 shadow-kanban-column dark:shadow-md rounded-xl flex flex-col max-h-full overflow-hidden">
                    <!-- Column Header -->
                    <div class="kanban-column-header flex items-center justify-between py-3 px-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <h3 class="text-sm font-medium text-kanban-column-header dark:text-kanban-column-header">
                                {{ $column['name'] }}
                            </h3>
                            <div class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium kanban-status-{{ str_replace('_', '-', $columnId) }}">
                                {{ $column['total'] ?? count($column['items']) }}
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="openCreateForm('{{ $columnId }}')"
                            x-on:click="$dispatch('open-modal', { id: 'create-card-modal' })"
                            class="text-gray-400 hover:text-primary-500 dark:text-gray-500 dark:hover:text-primary-400"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Column Content -->
                    <div
                        x-sortable
                        x-sortable-group="cards"
                        data-column-id="{{ $columnId }}"
                        @end.stop="$wire.updateColumnCards($event.to.getAttribute('data-column-id'), $event.to.sortable.toArray())"
                        class="p-3 flex-1 overflow-y-auto overflow-x-hidden"
                        style="max-height: calc(100vh - 13rem);"
                    >
                        @if (count($column['items']) > 0)
                            @foreach ($column['items'] as $card)
                                <div 
                                    class="kanban-card mb-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden cursor-pointer transition-all duration-150 hover:shadow-md"
                                    x-sortable-item="{{ $card['id'] }}"
                                    wire:click="openEditForm('{{ $card['id'] }}', '{{ $columnId }}')"
                                    x-on:click="$dispatch('open-modal', { id: 'edit-card-modal' })"
                                >
                                    <div class="p-3">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $card['title'] }}</h4>
                                        
                                        @if(isset($card['description']) && !empty($card['description']))
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $card['description'] }}</p>
                                        @endif
                                        
                                        @if(!empty($config['cardAttributes']))
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach($config['cardAttributes'] as $attribute => $label)
                                                    @if(isset($card[$attribute]) && !empty($card[$attribute]))
                                                        <div class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                            <span class="font-medium mr-1">{{ $label }}:</span>
                                                            <span>{{ $card[$attribute] }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            
                            @if($column['total'] > count($column['items']))
                                <button 
                                    wire:click="loadMoreItems('{{ $columnId }}')"
                                    class="w-full py-2 text-xs text-center text-primary-600 dark:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150 rounded"
                                >
                                    {{ __('Load more') }} ({{ count($column['items']) }} / {{ $column['total'] }})
                                </button>
                            @endif
                        @else
                            <div class="kanban-empty-column h-24 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 text-sm">
                                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>No items</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

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

    <x-filament::modal id="edit-card-modal" :heading="__('Edit :recordLabel', ['recordLabel' => $config['recordLabel'] ?? 'Card'])">
        {{ $this->editForm }}
        
        <x-slot name="footer">
            <div class="flex items-center justify-between">
                <x-filament::button
                    color="danger"
                    wire:click="deleteCard"
                >
                    {{ __('Delete') }}
                </x-filament::button>

                <div class="flex gap-x-3">
                    <x-filament::button
                        color="gray"
                        x-on:click="$dispatch('close-modal', { id: 'edit-card-modal' })"
                    >
                        {{ __('Cancel') }}
                    </x-filament::button>

                    <x-filament::button
                        wire:click="updateCard"
                    >
                        {{ __('Save Changes') }}
                    </x-filament::button>
                </div>
            </div>
        </x-slot>
    </x-filament::modal>
</div>
