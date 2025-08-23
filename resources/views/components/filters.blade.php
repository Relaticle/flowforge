{{-- Native Filament table filters - zero custom code needed! --}}
@if(method_exists($this, 'getTable') && $this->getTable()->isFilterable())
    <div class="fi-ta-header-toolbar mb-4">
        <div class="fi-ta-actions fi-align-start fi-wrapped">
            {{-- Use Filament's exact filter system --}}
            <x-filament::dropdown
                placement="bottom-start"
                width="2xl"
                max-height="24rem"
                class="fi-ta-filters-dropdown"
            >
                <x-slot name="trigger">
                    {{ $this->getTable()->getFiltersTriggerAction()->badge($this->getTable()->getActiveFiltersCount()) }}
                </x-slot>

                {{-- Filament's native filter component --}}
                <x-filament-tables::filters
                    :apply-action="$this->getTable()->getFiltersApplyAction()"
                    :form="$this->getTableFiltersForm()"
                    heading-tag="h4"
                />
            </x-filament::dropdown>

            {{-- Native filter indicators --}}
            @if($this->getTable()->isFiltered())
                <div class="fi-ta-filter-indicators flex gap-1">
                    @foreach($this->getTable()->getFilterIndicators() as $indicator)
                        <x-filament::badge color="primary" size="sm">
                            {{ $indicator->getLabel() }}
                        </x-filament::badge>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif