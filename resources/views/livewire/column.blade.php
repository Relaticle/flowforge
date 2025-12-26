@props(['columnId', 'column', 'config'])

@php
    use Relaticle\Flowforge\Support\ColorResolver;

    // Resolve the color once using our centralized resolver
    $resolvedColor = ColorResolver::resolve($column['color']);
    $isSemantic = ColorResolver::isSemantic($resolvedColor);

    // For non-semantic colors, get the color array
    $colorShades = $isSemantic ? null : $resolvedColor;
@endphp

<div
    class="flowforge-column w-[300px] min-w-[300px] flex-shrink-0 border border-gray-200 dark:border-gray-700 shadow-sm dark:shadow-md rounded-xl flex flex-col max-h-full overflow-hidden">
    <!-- Column Header -->
    <div class="flowforge-column-header flex items-center justify-between py-3 px-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            @if ($column['icon'] ?? null)
                <x-filament::icon :icon="$column['icon']" class="h-4 w-4 text-gray-500 dark:text-gray-400 me-2" />
            @endif
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ $column['label'] }}
            </h3>

            {{-- Count Badge --}}
            @if($isSemantic)
                {{-- Use native Filament badge for semantic colors --}}
                <x-filament::badge
                    tag="div"
                    :color="$resolvedColor"
                    class="ms-2"
                >
                    {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
                </x-filament::badge>
            @elseif($colorShades)
                {{-- Custom badge for Color arrays --}}
                <div
                    @style([
                        Filament\Support\get_color_css_variables($resolvedColor, shades: [50, 300, 600, 700])
                    ])
                    @class([
                        'ms-2 items-center border px-2 py-0.5 rounded-md text-xs font-semibold',
                        'bg-custom-50 dark:bg-custom-600/20',
                        'text-custom-700 dark:text-custom-300',
                        'border-custom-700/30 dark:border-custom-300/30',
                    ])>
                    {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
                </div>
            @else
                {{-- Fallback: simple gray badge if no color --}}
                <div class="ms-2 items-center border px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-50 dark:bg-gray-600/20 text-gray-700 dark:text-gray-300 border-gray-700/30 dark:border-gray-300/30">
                    {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
                </div>
            @endif
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
        class="flowforge-column-content p-3 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain kanban-cards"
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