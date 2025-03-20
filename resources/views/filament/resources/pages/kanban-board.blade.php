<x-filament-panels::page>
    <div class="p-4">
        @php
            $adapter = $this->getAdapter();
            $model = $adapter->getModel();
            $modelClass = get_class($model);
            $statusField = $adapter->getStatusField();
            $statusValues = $adapter->getStatusValues();
            $titleAttribute = $adapter->getTitleAttribute();
            $descriptionAttribute = $adapter->getDescriptionAttribute();
            $cardAttributes = $adapter->getCardAttributes();
        @endphp
        
        <livewire:relaticle.flowforge.livewire.kanban-board 
            :modelClass="$modelClass"
            :statusField="$statusField"
            :statusValues="$statusValues"
            :titleAttribute="$titleAttribute"
            :descriptionAttribute="$descriptionAttribute"
            :cardAttributes="$cardAttributes"
        />
    </div>
</x-filament-panels::page>
