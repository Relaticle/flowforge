@props(['columnId', 'swimlaneId', 'cell', 'config', 'hasPositionIdentifier'])

@php
    $cellKey = $columnId . '|' . $swimlaneId;
    $items = $cell['items'] ?? [];
    $total = $cell['total'] ?? 0;
@endphp

<div
    data-column-id="{{ $columnId }}"
    data-swimlane-id="{{ $swimlaneId }}"
    @if($hasPositionIdentifier)
        x-sortable
        x-sortable-group="cards-{{ $swimlaneId }}"
        @end.stop="handleSortableEnd($event)"
    @endif
    @if($total > count($items))
        @scroll.throttle.100ms="handleColumnScroll($event, '{{ $cellKey }}')"
    @endif
    class="flowforge-swimlane-cell p-2 overflow-x-hidden kanban-cards"
    style="min-height: 100px;"
>
    @if (count($items) > 0)
        @foreach ($items as $record)
            <x-flowforge::card
                :record="$record"
                :config="$config"
                :columnId="$columnId"
                wire:key="card-{{ $record['id'] }}"
            />
        @endforeach

        @if($total > count($items))
            <div
                x-intersect.margin.200px="handleSmoothScroll('{{ $cellKey }}')"
                class="w-full py-2 text-center"
            >
                <div x-show="isLoadingColumn('{{ $cellKey }}')"
                     x-transition
                     class="text-xs text-primary-600 dark:text-primary-400 flex items-center justify-center gap-2">
                    {{ __('flowforge::flowforge.loading_more_cards') }}
                </div>
            </div>
        @endif
    @else
        <div class="p-2 flex flex-col items-center justify-center h-full rounded-lg border border-dashed border-gray-200 dark:border-gray-700" style="min-height: 60px;">
            <p class="text-xs text-gray-400 dark:text-gray-500">—</p>
        </div>
    @endif
</div>
