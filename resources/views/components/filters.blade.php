@php
    use Filament\Support\Enums\IconSize;use Filament\Support\Icons\Heroicon;use Filament\Tables\Filters\Indicator;use Filament\Tables\View\TablesIconAlias;use Illuminate\View\ComponentAttributeBag;
    use Filament\Support\Facades\FilamentView;
    use Filament\Tables\View\TablesRenderHook;

    use function Filament\Support\generate_icon_html;use function Filament\Support\prepare_inherited_attributes;
    $table = $this->getTable();
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
                            'wire:click' => 'resetTableFiltersForm',
                            'wire:loading.remove.delay.' . config('filament.livewire_loading_delay', 'default') => '',
                            'wire:target' => 'resetTableFiltersForm',
                        ])
                    )
                "
                            >
                                {{ __('filament-tables::table.filters.actions.reset.label') }}
                            </x-filament::link>
                        </div>
                    </div>

                    {{ $this->getTableFiltersForm()  }}


                    @if ($table->getFiltersApplyAction()->isVisible())
                        <div class="fi-ta-filters-apply-action-ctn" style="padding-top: calc(var(--spacing) * 4)">
                            {{ $table->getFiltersApplyAction() }}
                        </div>
                    @endif
                </div>

            </x-filament::dropdown>
        @endif

        @if($isSearchable)
            {{-- Search input --}}
            <x-filament-tables::search-field
                :debounce="$table->getSearchDebounce()"
                :on-blur="$table->isSearchOnBlur()"
                :placeholder="$table->getSearchPlaceholder()"
            />
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
                                        $indicatorRemoveLivewireClickHandler = $indicator->getRemoveLivewireClickHandler();
                                    @endphp

                                    <x-slot
                                        name="deleteButton"
                                        :label="__('filament-tables::table.filters.actions.remove.label')"
                                        :wire:click="$indicatorRemoveLivewireClickHandler"
                                        wire:loading.attr="disabled"
                                        wire:target="removeTableFilter"
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
                        wire:click="removeTableFilters"
                        wire:loading.attr="disabled"
                        wire:target="removeTableFilters,removeTableFilter"
                        class="fi-icon-btn fi-size-sm -mt-1"
                    >
                        {{ generate_icon_html(Heroicon::XMark, alias: TablesIconAlias::FILTERS_REMOVE_ALL_BUTTON, size: IconSize::Small) }}
                    </button>
                @endif
            </div>
        @endif
    @endif
</div>
