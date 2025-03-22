@props(['columnId', 'column', 'config'])

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
        <button
            type="button"
            class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
            x-on:click="openCreateModal('{{ $columnId }}')"
        >
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    <!-- Column Content with Scroll Area -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden p-1">
        <template x-for="(card, index) in items" :key="card.id">
            <x-flowforge::kanban.card :config="$config" />
        </template>
    </div>
</div>
