@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Width;
    use Filament\Support\Icons\Heroicon;
    use Filament\Tables\Enums\FiltersLayout;
    use Filament\Tables\Filters\Indicator;
    use Filament\Tables\View\TablesIconAlias;

    use function Filament\Support\generate_icon_html;

    $table = $this->getTable();
    $isFilterable = $table->isFilterable();
    $isSearchable = $table->isSearchable();
    $filterIndicators = $table->getFilterIndicators();

    $filtersLayout = $table->getFiltersLayout();
    $filtersTriggerAction = $table->getFiltersTriggerAction();
    $filtersApplyAction = $table->getFiltersApplyAction();
    $filtersForm = $this->getTableFiltersForm();
    $filtersFormWidth = $table->getFiltersFormWidth();
    $filtersFormMaxHeight = $table->getFiltersFormMaxHeight();
    $filtersResetActionPosition = $table->getFiltersResetActionPosition();
    $activeFiltersCount = $table->getActiveFiltersCount();

    if (is_string($filtersFormWidth)) {
        $filtersFormWidth = Width::tryFrom($filtersFormWidth) ?? $filtersFormWidth;
    }

    $hasFiltersDialog = $isFilterable && in_array($filtersLayout, [FiltersLayout::Dropdown, FiltersLayout::Modal]);
@endphp

<style>
    .fi-board-header-toolbar .fi-dropdown-panel {
        background: white;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 12px;
    }
    .dark .fi-board-header-toolbar .fi-dropdown-panel {
        background: rgb(17, 24, 39);
        border-color: rgba(255, 255, 255, 0.1);
    }
</style>

<div class="fi-page-header-main-ctn fi-board-header-toolbar">
    {{-- Breadcrumbs --}}
    @if (filled($breadcrumbs))
        <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" class="mb-2" />
    @endif

    {{-- Title row: heading + filter + search --}}
    <div class="flex items-center gap-4">
        <div class="flex-1 min-w-0">
            <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                {{ $heading }}
            </h1>

            @if (filled($subheading))
                <p class="fi-header-subheading mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                    {{ $subheading }}
                </p>
            @endif
        </div>

        <div class="flex items-center gap-x-3 shrink-0">
            @if ($isFilterable && $hasFiltersDialog)
                <x-filament::dropdown
                    :max-height="$filtersFormMaxHeight"
                    placement="bottom-end"
                    shift
                    :flip="false"
                    :width="$filtersFormWidth ?? Width::ExtraSmall"
                    :wire:key="$this->getId() . '.board.filters'"
                    class="fi-ta-filters-dropdown"
                >
                    <x-slot name="trigger">
                        {{ $filtersTriggerAction->badge($activeFiltersCount) }}
                    </x-slot>

                    <x-filament-tables::filters
                        :apply-action="$filtersApplyAction"
                        :form="$filtersForm"
                        :reset-action-position="$filtersResetActionPosition"
                    />
                </x-filament::dropdown>
            @endif

            @if ($isSearchable)
                <x-filament-tables::search-field
                    :debounce="$table->getSearchDebounce()"
                    :on-blur="$table->isSearchOnBlur()"
                    :placeholder="$table->getSearchPlaceholder()"
                />
            @endif
        </div>
    </div>

    {{-- Active filter indicators --}}
    @if ($filterIndicators)
        <div class="fi-ta-filter-indicators flex items-center gap-x-2 mt-3">
            <div class="flex flex-wrap items-center gap-1">
                @foreach ($filterIndicators as $indicator)
                    <x-filament::badge :color="$indicator->getColor()">
                        {{ $indicator->getLabel() }}

                        @if ($indicator->isRemovable())
                            <x-slot
                                name="deleteButton"
                                :label="__('filament-tables::table.filters.actions.remove.label')"
                                :wire:click="$indicator->getRemoveLivewireClickHandler()"
                                wire:loading.attr="disabled"
                                wire:target="removeTableFilter"
                            ></x-slot>
                        @endif
                    </x-filament::badge>
                @endforeach
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
                    class="fi-icon-btn fi-size-sm"
                >
                    {{ generate_icon_html(Heroicon::XMark, alias: TablesIconAlias::FILTERS_REMOVE_ALL_BUTTON, size: IconSize::Small) }}
                </button>
            @endif
        </div>
    @endif
</div>
