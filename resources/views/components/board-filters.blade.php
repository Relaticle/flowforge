@php
    use Filament\Support\Enums\IconSize;use Filament\Support\Icons\Heroicon;use Filament\Tables\Filters\Indicator;use Filament\Tables\View\TablesIconAlias;use Illuminate\View\ComponentAttributeBag;
    use Filament\Support\Facades\FilamentView;
    use Filament\Tables\View\TablesRenderHook;

    use function Filament\Support\generate_icon_html;use function Filament\Support\prepare_inherited_attributes;
    
    // Use the isolated board table instead of the shared page table
    $table = $this->getBoardTable();
    $isFilterable = $table->isFilterable();
    $isFiltered = $table->isFiltered();
    $isSearchable = $table->isSearchable();
    $filterIndicators = $table->getFilterIndicators();
@endphp


<div class="fi-ta-header-toolbar mb-4 ms-2">
    <div class="fi-ta-actions fi-align-start fi-wrapped space-x-4">
        @if($isFilterable)
            <x-filament::dropdown
                placement="bottom-start"
                :width="$table->getFiltersFormWidth()"
                :max-height="$table->getFiltersFormMaxHeight()"
                class="fi-ta-filters-dropdown z-40"
            >
                <x-slot name="trigger">
                    {{ $table->getFiltersTriggerAction()->badge($table->getActiveFiltersCount()) }}
                </x-slot>

                <div class="fi-ta-filters-dropdown-panel" style="padding: calc(var(--spacing) * 6); ">
                    <div class="fi-ta-filters-header mb-4 flex items-center justify-between">
                        <h2 class="fi-ta-filters-heading font-medium">
                            {{ __('filament-tables::table.filters.heading') }}
                        </h2>

                        <div>
                            <x-filament::link
                                :attributes="
                    prepare_inherited_attributes(
                        new ComponentAttributeBag([
                            'color' => 'danger',
                            'tag' => 'button',
                            'wire:click' => 'resetBoardTableFiltersForm',
                            'wire:loading.remove.delay.' . config('filament.livewire_loading_delay', 'default') => '',
                            'wire:target' => 'resetBoardTableFiltersForm',
                        ])
                    )
                "
                            >
                                {{ __('filament-tables::table.filters.actions.reset.label') }}
                            </x-filament::link>
                        </div>
                    </div>

                    {{ $this->getBoardTableFiltersForm()  }}


                    @if ($table->getFiltersApplyAction()->isVisible())
                        <div class="fi-ta-filters-apply-action-ctn" style="padding-top: calc(var(--spacing) * 4)">
                            {{ $table->getFiltersApplyAction() }}
                        </div>
                    @endif
                </div>

            </x-filament::dropdown>
        @endif

        @if($isSearchable)
            {{-- Board-specific search input --}}
            <div class="fi-ta-search-field-wrapper relative">
                <input
                    wire:model.live.debounce.500ms="boardTableSearch"
                    placeholder="{{ $table->getSearchPlaceholder() }}"
                    type="search"
                    class="fi-input block w-full border-none py-1.5 pl-10 pr-3 text-base text-gray-950 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white dark:bg-white/5 ring-1 ring-gray-950/10 hover:ring-gray-950/20 focus:ring-primary-600 dark:ring-white/10 dark:hover:ring-white/20 dark:focus:ring-primary-500"
                />
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    {{ generate_icon_html(\Filament\Support\Icons\Heroicon::MagnifyingGlass, size: IconSize::Small, classes: 'text-gray-400 dark:text-gray-500') }}
                </div>
            </div>
        @endif
    </div>

    @if ($filterIndicators)
        @if (filled($filterIndicatorsView = FilamentView::renderHook(TablesRenderHook::FILTER_INDICATORS, scopes: static::class, data: ['filterIndicators' => $filterIndicators])))
            {{ $filterIndicatorsView }}
        @else
            <div class="fi-ta-filter-indicators flex items-start justify-between gap-x-3 bg-gray-50 pt-3 dark:bg-white/5">
                <div class="flex flex-col gap-x-3 gap-y-1 sm:flex-row">
                        <span class="fi-ta-filter-indicators-label text-sm leading-6 font-medium whitespace-nowrap text-gray-700 dark:text-gray-200">
                            {{ __('filament-tables::table.filters.indicator') }}
                        </span>

                    <div class="fi-ta-filter-indicators-badges-ctn flex flex-wrap gap-1.5">
                        @foreach ($filterIndicators as $indicator)
                            @php
                                $indicatorColor = $indicator->getColor();
                            @endphp

                            <x-filament::badge :color="$indicatorColor">
                                {{ $indicator->getLabel() }}

                                @if ($indicator->isRemovable())
                                    @php
                                        $indicatorRemoveLivewireClickHandler = str_replace('removeTableFilter', 'removeBoardTableFilter', $indicator->getRemoveLivewireClickHandler());
                                    @endphp

                                    <x-slot
                                        name="deleteButton"
                                        :label="__('filament-tables::table.filters.actions.remove.label')"
                                        :wire:click="$indicatorRemoveLivewireClickHandler"
                                        wire:loading.attr="disabled"
                                        wire:target="removeBoardTableFilter"
                                    ></x-slot>
                                @endif
                            </x-filament::badge>
                        @endforeach
                    </div>
                </div>

                @if (collect($filterIndicators)->contains(fn (Indicator $indicator): bool => $indicator->isRemovable()))
                    <button
                        type="button"
                        x-tooltip="{
                                content: @js(__('filament-tables::table.filters.actions.remove_all.tooltip')),
                                theme: $store.theme,
                            }"
                        wire:click="removeBoardTableFilters"
                        wire:loading.attr="disabled"
                        wire:target="removeBoardTableFilters,removeBoardTableFilter"
                        class="fi-icon-btn fi-size-sm -mt-1"
                    >
                        {{ generate_icon_html(Heroicon::XMark, alias: TablesIconAlias::FILTERS_REMOVE_ALL_BUTTON, size: IconSize::Small) }}
                    </button>
                @endif
            </div>
        @endif
    @endif
</div>