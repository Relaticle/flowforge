<div class="w-full h-full flex flex-col items-center justify-center py-12">
    <div class="relative w-16 h-16">
        <!-- Outer spinner -->
        <div class="absolute inset-0 rounded-full border-4 border-t-transparent border-primary-200 dark:border-primary-900/50 animate-spin"></div>

        <!-- Inner spinner -->
        <div class="absolute inset-2 rounded-full border-4 border-t-transparent border-primary-500 dark:border-primary-400 animate-spin" style="animation-duration: 0.6s;"></div>
    </div>

    <p class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-400">{{ $slot ?? 'Loading...' }}</p>
</div>
