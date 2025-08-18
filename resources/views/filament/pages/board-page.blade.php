<x-filament-panels::page>
    <div class="h-[calc(100vh-12rem)]">
        @livewire('relaticle.flowforge.livewire.kanban-board', [
            'adapter' => $this->getAdapter(),
            'pageClass' => $this->getPageClass(),
        ])
    </div>
</x-filament-panels::page>
