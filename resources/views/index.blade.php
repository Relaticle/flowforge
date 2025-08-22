@php use Filament\Support\Facades\FilamentAsset; @endphp
@props(['columns', 'config'])

<div
    class="w-full h-full flex flex-col relative"
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
        state: {
            columns: @js($columns),
            titleField: '{{ $config->getTitleField() }}',
            columnField: '{{ $config->getColumnField() }}',
            cardLabel: '{{ $config->getSingularCardLabel() }}',
            pluralCardLabel: '{{ $config->getPluralCardLabel() }}'
        }
    })"
>

    {{-- Board Filters - Rendered exactly like Filament tables --}}
    @if(method_exists($this, 'getBoard') && $this->getBoard()->hasBoardFilters())
        <div class="mb-4">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900">Filters</h3>
                        <div class="flex items-center gap-2">
                            @if($this->getBoard()->hasDeferredFilters())
                                <button
                                    type="button"
                                    wire:click="applyBoardFilters"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    Apply Filters
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="resetBoardFiltersForm"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    {{-- Active Filter Indicators --}}
                    @if(method_exists($this, 'hasActiveBoardFilters') && $this->hasActiveBoardFilters())
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach($this->getActiveBoardFilterIndicators() as $indicator)
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-md">
                                    {{ $indicator instanceof \Filament\Tables\Filters\Indicator ? $indicator->getLabel() : $indicator }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Filter Form - Using Filament's form system --}}
                <div class="p-4">
                    {{ $this->getBoardFiltersForm() }}
                </div>
            </div>
        </div>
    @endif

    <!-- Board Content -->
    <div class="flex-1 overflow-hidden">
        <div class="flex flex-row h-full overflow-x-auto overflow-y-hidden px-2 gap-5 pb-4">
            @foreach($columns as $columnId => $column)
                <x-flowforge::column
                    :columnId="$columnId"
                    :column="$column"
                    :config="$config"
                    wire:key="column-{{ $columnId }}"
                />
            @endforeach
        </div>
    </div>

    <x-filament-actions::modals/>
</div>
