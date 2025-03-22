<div
    class="w-full h-full flex flex-col"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('flowforge', package: 'relaticle/flowforge'))]"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flowforge', 'relaticle/flowforge') }}"
>
    <!-- Board Content -->
    <div class="flex flex-row h-full w-full overflow-x-auto overflow-y-hidden py-4 px-2 gap-4">
        @foreach($columns as $columnId => $column)
            <div
                class="flex flex-col h-full min-w-64 w-64 bg-gray-100 dark:bg-gray-800 rounded-xl shadow-sm p-2"
                x-data="{
                    columnId: '{{ $columnId }}',
                    items: @js($column['items']),
                    isOver: false
                }"
                x-on:dragover.prevent="isOver = true; $event.dataTransfer.dropEffect = 'move';"
                x-on:dragleave.prevent="isOver = false"
                x-on:drop.prevent="
                    isOver = false;
                    const data = JSON.parse($event.dataTransfer.getData('text/plain'));
                    $wire.updateStatus(data.id, columnId);
                "
                :class="{ 'border-2 border-primary-500 dark:border-primary-400': isOver }"
            >
                <!-- Column Header -->
                <div class="flex items-center justify-between p-2 mb-2 font-medium">
                    <h3 class="text-gray-900 dark:text-white">{{ $column['name'] }}</h3>
                </div>

                <!-- Column Content -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden p-1">
                    <template x-for="(card, index) in items" :key="card.id">
                        <div
                            class="bg-white dark:bg-gray-700 rounded-md shadow-sm mb-2 p-3 cursor-grab select-none transition-all duration-200 hover:shadow-md hover:-translate-y-[2px]"
                            :class="{
                                'border-l-4 border-red-500': card.priority === 'high',
                                'border-l-4 border-yellow-500': card.priority === 'medium',
                                'border-l-4 border-green-500': card.priority === 'low',
                                'border-l-4 border-gray-300 dark:border-gray-600': !card.priority
                            }"
                            draggable="true"
                            x-on:dragstart="
                                $event.dataTransfer.setData('text/plain', JSON.stringify({
                                    id: card.id,
                                    sourceColumn: columnId
                                }));
                                $event.target.classList.add('opacity-50');
                            "
                            x-on:dragend="$event.target.classList.remove('opacity-50')"
                        >
                            <div class="text-sm font-medium text-gray-900 dark:text-white mb-1" x-text="card.title"></div>

                            <template x-if="card.description">
                                <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2" x-text="card.description"></div>
                            </template>

                            <div class="flex flex-wrap gap-2 mt-2">
                                <template x-for="(value, key) in card" :key="key">
                                    <template x-if="!['id', 'title', 'description'].includes(key) && value">
                                        <div
                                            class="inline-flex items-center text-xs px-2 py-0.5 rounded-full"
                                            :class="{
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': key === 'category',
                                                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300': key === 'assignee',
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': key === 'due_date',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300': !['category', 'assignee', 'due_date'].includes(key)
                                            }"
                                        >
                                            <span x-text="value"></span>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        @endforeach
    </div>
</div>
