{{-- Board Filters - Filament-style UI with proper form rendering --}}
@if(method_exists($this, 'getBoard') && $this->getBoard()->hasBoardFilters())
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            {{-- Filament Filter Trigger Button (manual implementation that works) --}}
            <div class="relative" x-data="{ open: false }">
                <x-filament::icon-button
                    icon="heroicon-m-funnel"
                    size="sm"
                    color="gray"
                    :badge="method_exists($this, 'hasActiveBoardFilters') && $this->hasActiveBoardFilters() ? count($this->getActiveBoardFilterIndicators()) : null"
                    x-on:click="open = !open"
                >
                    Filters
                </x-filament::icon-button>

                {{-- Filter Dropdown with improved layout --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.away="open = false"
                    class="absolute left-0 z-50 mt-2 w-96 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
                    style="display: none;"
                >
                    {{-- Header --}}
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Filter Tasks</h3>
                    </div>

                    {{-- Filter Form with improved spacing --}}
                    <div class="p-4 max-h-96 overflow-y-auto">
                        <div class="space-y-4">
                            @if(method_exists($this, 'getBoardFiltersForm'))
                                {{ $this->getBoardFiltersForm() }}
                            @endif
                        </div>
                    </div>

                    {{-- Footer with actions --}}
                    @if($this->getBoard()->hasDeferredFilters())
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                            <x-filament::button
                                wire:click="resetBoardFiltersForm"
                                color="gray"
                                size="sm"
                            >
                                Reset All
                            </x-filament::button>

                            <x-filament::button
                                wire:click="applyBoardFilters"
                                x-on:click="open = false"
                                size="sm"
                                color="primary"
                            >
                                Apply Filters
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Active Filter Indicators using Filament badges --}}
            @if(method_exists($this, 'hasActiveBoardFilters') && $this->hasActiveBoardFilters())
                <div class="flex flex-wrap gap-1.5">
                    @foreach($this->getActiveBoardFilterIndicators() as $indicator)
                        <x-filament::badge color="primary" size="sm">
                            {{ $indicator instanceof \Filament\Tables\Filters\Indicator ? $indicator->getLabel() : $indicator }}
                        </x-filament::badge>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
