<x-filament::page>
    <div class="h-[calc(100vh-12rem)]">
        @livewire('relaticle.flowforge.livewire.kanban-board', [
            'adapter' => $this->getAdapter(),
        ])
    </div>
</x-filament::page>
