@props([
    'filters' => [],
    'filterData' => [],
    'formStatePath' => 'boardFilterData',
    'deferFilters' => true,
    'formSchema' => [],
    'hasFilters' => false,
])

@if($hasFilters)
<div class="flowforge-filter-bridge">
    {{-- Filter Toggle Button --}}
    <div class="flex items-center justify-between mb-4">
        <button 
            type="button"
            wire:click="$toggle('showBoardFilters')"
            @class([
                'flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                'bg-primary-50 text-primary-700 border border-primary-200 hover:bg-primary-100' => $this->hasActiveBoardFilters(),
                'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100' => !$this->hasActiveBoardFilters(),
            ])
        >
            <x-filament::icon 
                icon="heroicon-m-funnel" 
                class="w-4 h-4" 
            />
            
            <span>Filters</span>
            
            @if($this->hasActiveBoardFilters())
                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium text-white bg-primary-600 rounded-full">
                    {{ count($this->getActiveBoardFilterIndicators()) }}
                </span>
            @endif
        </button>

        {{-- Active Filter Indicators --}}
        @if($this->hasActiveBoardFilters())
            <div class="flex items-center gap-2">
                @foreach($this->getActiveBoardFilterIndicators() as $indicator)
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-md">
                        {{ $indicator }}
                        <button 
                            type="button"
                            wire:click="resetBoardFilters"
                            class="p-0.5 text-primary-500 hover:text-primary-700"
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" class="w-3 h-3" />
                        </button>
                    </span>
                @endforeach
                
                <button 
                    type="button"
                    wire:click="resetBoardFilters"
                    class="text-xs text-gray-500 hover:text-gray-700"
                >
                    Clear all
                </button>
            </div>
        @endif
    </div>

    {{-- Filter Form Panel --}}
    @if($this->showBoardFilters ?? false)
        <div class="flowforge-filter-panel mb-6 p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
            <form wire:submit.prevent="{{ $deferFilters ? 'applyBoardFilters' : '' }}">
                {{-- Filter Form Schema --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($formSchema as $component)
                        {{ $component }}
                    @endforeach
                </div>

                {{-- Filter Actions --}}
                @if($deferFilters)
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                        <button 
                            type="button"
                            wire:click="resetBoardFilters"
                            class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            Reset Filters
                        </button>

                        <button 
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            Apply Filters
                        </button>
                    </div>
                @endif
            </form>
        </div>
    @endif
</div>

<style>
    /* Flowforge-specific filter styling */
    .flowforge-filter-bridge {
        /* Ensure filters blend seamlessly with Flowforge's design */
    }
    
    .flowforge-filter-panel {
        /* Custom styling for the filter panel */
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
    }
    
    .flowforge-filter-panel .fi-fo-field-wrp {
        /* Override Filament form field styling for better Flowforge integration */
        margin-bottom: 0.75rem;
    }
    
    .flowforge-filter-panel .fi-section {
        /* Section styling within filter panel */
        background: white;
        border-radius: 0.5rem;
        padding: 1rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .flowforge-filter-panel .grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endif