<x-filament-panels::page>
    @livewire('relaticle.flowforge.livewire.kanban-board', [
        'adapter' => $this->getAdapter(),
        'pageClass' => $this->getPageClass(),
    ])
</x-filament-panels::page>
