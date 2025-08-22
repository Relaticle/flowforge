@props(['columnId', 'column', 'config'])

<div class="w-[300px] min-w-[300px] flex-shrink-0 border border-gray-200 dark:border-gray-700 shadow-sm dark:shadow-md rounded-xl flex flex-col max-h-full overflow-hidden">
    <!-- Column Header -->
    <div class="flex items-center justify-between py-3 px-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ $column['label'] }}
            </h3>
            <div @class([
                'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                // Column color classes based on color prop
                'bg-gray-100 text-gray-800 border border-gray-200 hover:bg-gray-200 dark:bg-gray-700/60 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700' => ($column['color'] ?? 'default') === 'gray' || ($column['color'] ?? 'default') === 'default',
                'bg-blue-50 text-blue-800 border border-blue-200 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-200 dark:border-blue-800/60 dark:hover:bg-blue-900/60' => ($column['color'] ?? 'default') === 'blue',
                'bg-green-50 text-green-800 border border-green-200 hover:bg-green-100 dark:bg-green-900/40 dark:text-green-200 dark:border-green-800/60 dark:hover:bg-green-900/60' => ($column['color'] ?? 'default') === 'green',
                'bg-red-50 text-red-800 border border-red-200 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-200 dark:border-red-800/60 dark:hover:bg-red-900/60' => ($column['color'] ?? 'default') === 'red',
                'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 dark:bg-amber-900/40 dark:text-amber-200 dark:border-amber-800/60 dark:hover:bg-amber-900/60' => ($column['color'] ?? 'default') === 'amber',
            ])>
                {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
            </div>
        </div>


        {{-- Column actions are always visible --}}
        @php
            $processedActions = $this->getColumnActionsForColumn($columnId);
        @endphp

        @if(count($processedActions) > 0)
            <div>
                @if(count($processedActions) === 1)
                    {{ $processedActions[0] }}
                @else
                    <x-filament-actions::group :actions="$processedActions"/>
                @endif
            </div>
        @endif
    </div>

    <!-- Column Content -->
    <div
        @if($this->getBoard()->getReorderBy() !== null)
            x-sortable
            x-sortable-group="cards"
            data-column-id="{{ $columnId }}"
            @end.stop="$wire.updateRecordsOrderAndColumn($event.to.getAttribute('data-column-id'), $event.to.sortable.toArray())"
        @endif
        class="p-3 flex-1 overflow-y-auto overflow-x-hidden overscroll-contain"
        style="max-height: calc(100vh - 13rem);"
    >
        @if (isset($column['items']) && count($column['items']) > 0)
            @foreach ($column['items'] as $record)
                <x-flowforge::card
                    :record="$record"
                    :config="$config"
                    :columnId="$columnId"
                    wire:key="card-{{ $record['id'] }}"
                />
            @endforeach

            @if(isset($column['total']) && $column['total'] > count($column['items']))
                <div
                    x-intersect.full="
                        if (!isLoadingColumn('{{ $columnId }}')) {
                            beginLoading('{{ $columnId }}');
                            $wire.loadMoreItems('{{ $columnId }}', {{ $this->cardsIncrement ?? 'null' }});
                        }
                    "
                    class="py-3 text-center"
                >
                    <div wire:loading wire:target="loadMoreItems('{{ $columnId }}')"
                         class="text-xs text-primary-600 dark:text-primary-400">
                        {{ __('flowforge::flowforge.loading_more_cards') }}
                        <div class="mt-1 flex justify-center">
                            <svg class="animate-spin h-4 w-4 text-primary-600 dark:text-primary-400"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <div wire:loading.remove wire:target="loadMoreItems('{{ $columnId }}')"
                         class="text-xs text-gray-400">
                        {{ count($column['items']) }}
                        / {{ $column['total'] }} {{ $config->getPluralCardLabel() }}
                    </div>
                </div>
            @endif
        @else
            <x-flowforge::empty-column
                :columnId="$columnId"
                :pluralCardLabel="$config->getPluralCardLabel()"
            />
        @endif
    </div>
</div>
