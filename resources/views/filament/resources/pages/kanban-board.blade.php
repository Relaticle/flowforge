<x-filament::page
    :class="
        \Illuminate\Support\Arr::toCssClasses([
            'filament-resources-list-records-page',
            'filament-flowforge-page',
        ])
    "
>
    <div class="filament-flowforge-kanban-container h-[calc(100vh-12rem)]">
        @livewire('relaticle.flowforge.livewire.kanban-board', [
            'adapter' => $this->getAdapter(),
        ])
    </div>

    @if(method_exists($this, 'mountAction') && method_exists($this, 'isModalSlideOver'))
        <x-filament::modal
            id="flowforge-create-record"
            width="lg"
            :slide-over="$this->isModalSlideOver()"
            display-classes="block"
            x-on:open-modal.window="if ($event.detail.id === 'flowforge-create-record') {
                close()
                $wire.mountAction('create')
            }">
        </x-filament::modal>
    @endif
</x-filament::page>
