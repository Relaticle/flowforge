@php use Filament\Support\Enums\Width;use Illuminate\View\ComponentAttributeBag;use function Filament\Support\prepare_inherited_attributes; @endphp
{{-- Board Filters - Using Filament's exact container structure --}}
@if(method_exists($this, 'getBoard') && $this->getBoard()->hasBoardFilters())
    @php
        $activeFiltersCount = method_exists($this, 'hasActiveBoardFilters') && $this->hasActiveBoardFilters()
            ? count($this->getActiveBoardFilterIndicators())
            : 0;
        $filtersForm = method_exists($this, 'getBoardFiltersForm') ? $this->getBoardFiltersForm() : null;
//         $filtersFormWidth = $getFiltersFormWidth();
        $filtersApplyAction = $this->getBoard()->getBoardFiltersApplyAction();
    @endphp

    {{-- Use Filament's exact toolbar container structure --}}
    <div class="fi-ta-header-toolbar mb-4">
        <div class="fi-ta-actions fi-align-start fi-wrapped">
            {{-- Filament Filter Dropdown - Exact same as Filament tables --}}
            <x-filament::dropdown
                placement="bottom-start"
                shift
                :width="$filtersFormWidth ?? Width::Small"
                max-height="28rem"
                class="fi-ta-filters-dropdown z-50"
            >
                <x-slot name="trigger">
                    <x-filament::icon-button
                        icon="heroicon-m-funnel"
                        size="sm"
                        color="gray"
                        :badge="$activeFiltersCount ?: null"
                        badge-color="primary"
                    >
                        Filters
                    </x-filament::icon-button>
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

                    {{-- Use Filament's exact filters component --}}
                    {{ $filtersForm }}

                    @if ($filtersApplyAction->isVisible())
                        <div class="fi-ta-filters-apply-action-ctn" style="padding-top: calc(var(--spacing) * 4)">
                            {{ $filtersApplyAction }}
                        </div>
                    @endif
                </div>
            </x-filament::dropdown>

            {{-- Active Filter Indicators using Filament's exact styling --}}
            @if($activeFiltersCount > 0)
                <div class="fi-ta-filter-indicators flex flex-wrap gap-1.5">
                    @foreach($this->getActiveBoardFilterIndicators() as $indicator)
                        <x-filament::badge
                            color="primary"
                            size="sm"
                            class="fi-ta-filter-indicator"
                        >
                            {{ $indicator instanceof \Filament\Tables\Filters\Indicator ? $indicator->getLabel() : $indicator }}
                        </x-filament::badge>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
