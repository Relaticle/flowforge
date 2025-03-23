@props(['columnId'])

<div class="kanban-empty-column h-24 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg flex flex-col items-center justify-center px-4 py-5 text-center">
    <div class="flex justify-center items-center">
        <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
    </div>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No items in this column</p>
    <button
        type="button"
        @click="openCreateModal('{{ $columnId }}')"
        class="mt-2 inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-primary-700 bg-primary-100 hover:bg-primary-200 dark:text-primary-300 dark:bg-primary-900/30 dark:hover:bg-primary-800/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
    >
        Add Card
    </button>
</div>
