@props(['columnId'])

<div class="p-3 flex flex-col items-center justify-center h-full min-h-[150px] rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-800">
    <svg class="w-10 h-10 text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
    </svg>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('No cards in this column') }}
    </p>
</div>
