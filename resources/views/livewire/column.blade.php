@props(['columnId', 'column', 'config'])

@php
    use Filament\Support\Colors\Color;
    $colors = Color::all();

    if(filled($column['color'])) {
        $color = $colors[$column['color']] ?? Color::generateV3Palette($column['color']);
    }
    else {
        $color = $colors['neutral'];
    }
@endphp

<div
        class="w-[300px] min-w-[300px] flex-shrink-0 border border-gray-200 dark:border-gray-700 shadow-sm dark:shadow-md rounded-xl flex flex-col max-h-full overflow-hidden">
    <!-- Column Header -->
    <div class="flex items-center justify-between py-3 px-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ $column['label'] }}
            </h3>
            <div
                    style="background-color: {{ $color[500] }};  color: {{ $color[50] }};"
                    @class([
                    'ms-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                ])>
                {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
            </div>
        </div>


        {{-- Column actions are always visible --}}
        @php
            $processedActions = $this->getBoardColumnActions($columnId);
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
            data-column-id="{{ $columnId }}"
            @if($this->getBoard()->getPositionIdentifierAttribute())
                x-sortable
            x-sortable-group="cards"
            @end.stop="handleSortableEnd($event)"
            @endif
            @if(isset($column['total']) && $column['total'] > count($column['items']))
                @scroll.throttle.100ms="handleColumnScroll($event, '{{ $columnId }}')"
            @endif
            class="p-3 flex-1 overflow-y-auto overflow-x-hidden overscroll-contain kanban-cards"
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

            {{-- Always show status message at bottom --}}
            <div class="py-3 text-center">
                @if(isset($column['total']) && $column['total'] > count($column['items']))
                    {{-- More items available --}}
                    <div
                            x-intersect.margin.300px="handleSmoothScroll('{{ $columnId }}')"
                            class="w-full">

                        <div x-show="isLoadingColumn('{{ $columnId }}')"
                             x-transition
                             class="text-xs text-primary-600 dark:text-primary-400 flex items-center justify-center gap-2">
                            {{ __('flowforge::flowforge.loading_more_cards') }}
                        </div>
                    </div>
                @endif
            </div>
        @else
            <x-flowforge::empty-column
                    :columnId="$columnId"
                    :pluralCardLabel="$config['pluralCardLabel']"
            />
        @endif
    </div>
</div>
