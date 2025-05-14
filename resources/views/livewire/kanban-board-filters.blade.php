<div class="mb-4 p-4 bg-white rounded-lg shadow space-y-4">
    <div class="flex flex-wrap gap-2">
        @foreach($config->getFilterableFields() as $field => $config)
            <div class="flex-shrink-0">
                @if($config['type'] === 'select')
                    <div>
                        <label for="filter-{{ $field }}" class="block text-sm font-medium text-gray-700">
                            {{ $config['label'] ?? ucfirst($field) }}
                        </label>
                        <select
                            id="filter-{{ $field }}"
                            wire:model.live="filters.{{ $field }}"
                            wire:change="applyFilter('{{ $field }}', $event.target.value)"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 text-sm"
                        >
                            <option value="">{{ __('All') }}</option>
                            @foreach($this->getFilterOptions($field) as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($config['type'] === 'text')
                    <div>
                        <label for="filter-{{ $field }}" class="block text-sm font-medium text-gray-700">
                            {{ $config['label'] ?? ucfirst($field) }}
                        </label>
                        <input
                            type="text"
                            id="filter-{{ $field }}"
                            wire:model.live.debounce.300ms="filters.{{ $field }}"
                            wire:change="applyFilter('{{ $field }}', $event.target.value)"
                            placeholder="{{ __('Search') }}..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 text-sm"
                        />
                    </div>
                @elseif($config['type'] === 'date')
                    <div>
                        <label for="filter-{{ $field }}" class="block text-sm font-medium text-gray-700">
                            {{ $config['label'] ?? ucfirst($field) }}
                        </label>
                        <input
                            type="date"
                            id="filter-{{ $field }}"
                            wire:model.live="filters.{{ $field }}"
                            wire:change="applyFilter('{{ $field }}', $event.target.value)"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 text-sm"
                        />
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    
    @if(count($filters))
        <div class="flex items-center justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach($filters as $field => $value)
                    @if($value)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-primary-100 text-primary-800">
                            {{ $config->getFilterableFields()[$field]['label'] ?? ucfirst($field) }}: {{ $this->formatFilterValue($field, $value) }}
                            <button type="button" wire:click="removeFilter('{{ $field }}')" class="ml-1 inline-flex text-primary-400 hover:text-primary-600">
                                <span class="sr-only">{{ __('Remove filter') }}</span>
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif
                @endforeach
            </div>
            
            <button
                wire:click="resetFilters"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                {{ __('Clear All Filters') }}
            </button>
        </div>
    @endif
</div>
