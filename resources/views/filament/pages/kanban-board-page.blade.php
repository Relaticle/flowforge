<x-filament::page class="filament-flowforge-page">
    <div class="filament-flowforge-kanban-container h-[calc(100vh-12rem)]">
        @livewire('relaticle.flowforge.livewire.kanban-board', [
            'adapter' => $this->getAdapter(),
        ])
    </div>
</x-filament::page>
