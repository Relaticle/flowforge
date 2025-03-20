<div class="flowforge-kanban-board">
    <!-- Board header with controls -->
    <div class="flowforge-kanban-header">
        <div>
            <h2 class="text-lg font-semibold">{{ $boardTitle ?? 'Kanban Board' }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span>{{ $this->getTotalItemCount() }}</span> items total
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="$refresh" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Loading state overlay -->
    <div wire:loading.delay class="flowforge-kanban-loading">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm transition ease-in-out duration-150">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
        </div>
    </div>

    <!-- Columns container with horizontal scrolling -->
    <div class="kanban-columns-container">
        @foreach($columnLabels as $value => $label)
            <div 
                class="flowforge-kanban-column" 
                data-column-id="{{ $value }}"
                x-data="kanbanDragDrop('{{ $value }}')"
            >
                <div class="flowforge-kanban-column-header">
                    <span>{{ $label }}</span>
                    <div wire:key="count-{{ $value }}" class="flowforge-kanban-column-count">
                        <span>{{ $columns[$value]->count() }}</span>
                    </div>
                </div>
                
                <div 
                    class="flowforge-kanban-column-body"
                    x-on:dragover.prevent="handleDragOver"
                    x-on:dragenter.prevent="handleDragEnter"
                    x-on:dragleave="handleDragLeave"
                    x-on:drop="handleDrop($event, $wire)"
                >
                    <!-- Drop placeholder -->
                    <template x-if="showPlaceholder">
                        <div class="flowforge-kanban-drop-placeholder"></div>
                    </template>
                    
                    @if($columns[$value]->count() > 0)
                        @foreach($columns[$value] as $item)
                            <div 
                                class="flowforge-kanban-card" 
                                draggable="true"
                                x-on:dragstart="handleDragStart($event, '{{ $item->id }}')"
                                x-on:dragend="handleDragEnd"
                                data-id="{{ $item->id }}"
                                tabindex="0"
                                @if(isset($item->priority)) data-priority="{{ $item->priority }}" @endif
                            >
                                <div class="flowforge-kanban-card-title">
                                    {{ $item->{$titleAttribute} }}
                                </div>
                                
                                @if($descriptionAttribute && !empty($item->{$descriptionAttribute}))
                                    <div class="flowforge-kanban-card-description">
                                        {{ $item->{$descriptionAttribute} }}
                                    </div>
                                @endif
                                
                                @if(count($cardAttributes) > 0)
                                    <div class="flowforge-kanban-card-attributes">
                                        @foreach($cardAttributes as $attribute => $label)
                                            @if(isset($item->{$attribute}) && !empty($item->{$attribute}))
                                                <div class="flowforge-kanban-card-attribute">
                                                    <span class="font-medium">{{ $label }}:</span>
                                                    <span>{{ $item->{$attribute} }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="flowforge-kanban-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p>No items in this column</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Keyboard accessibility instructions -->
    <div class="sr-only" aria-live="polite">
        Use arrow keys to navigate between cards. Press Enter to select a card.
    </div>
</div>
