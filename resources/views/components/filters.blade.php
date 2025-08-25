@php
    use Illuminate\View\ComponentAttributeBag;use function Filament\Support\prepare_inherited_attributes;
    $isFilterable = $this->getTable()->isFilterable();
    $isFiltered = $this->getTable()->isFiltered();
    $isSearchable = $this->getTable()->isSearchable();
@endphp


<div class="fi-ta-header-toolbar mb-4 ms-2">
    <div class="fi-ta-actions fi-align-start fi-wrapped space-x-4 mb-2">
        @if($isFilterable)
            <x-filament::dropdown
                placement="bottom-start"
                :width="$this->getTable()->getFiltersFormWidth()"
                :max-height="$this->getTable()->getFiltersFormMaxHeight()"
                class="fi-ta-filters-dropdown z-40"
            >
                <x-slot name="trigger">
                    {{ $this->getTable()->getFiltersTriggerAction()->badge($this->getTable()->getActiveFiltersCount()) }}
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


                    @if ($this->getTable()->getFiltersApplyAction()->isVisible())
                        <div class="fi-ta-filters-apply-action-ctn" style="padding-top: calc(var(--spacing) * 4)">
                            {{ $this->getTable()->getFiltersApplyAction() }}
                        </div>
                    @endif
                </div>

            </x-filament::dropdown>
        @endif

        @if($isSearchable)
            {{-- Search input --}}
            <x-filament-tables::search-field
                :debounce="$this->getTable()->getSearchDebounce()"
                :on-blur="$this->getTable()->isSearchOnBlur()"
                :placeholder="$this->getTable()->getSearchPlaceholder()"
            />
        @endif
    </div>

    @if($isFiltered)
        <div class="fi-ta-filter-indicators flex gap-1">
            @foreach($this->getTable()->getFilterIndicators() as $indicator)
                <x-filament::badge color="primary" size="sm">
                    {{ $indicator->getLabel() }}
                </x-filament::badge>
            @endforeach
        </div>
    @endif
</div>
