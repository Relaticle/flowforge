// Flowforge Kanban Board JavaScript

document.addEventListener('alpine:init', () => {
    Alpine.data('kanbanDragDrop', (columnId) => ({
        draggedItem: null,
        dropColumn: columnId,
        
        handleDragStart(event, itemId) {
            event.dataTransfer.setData('text/plain', itemId);
            event.dataTransfer.effectAllowed = 'move';
            this.draggedItem = itemId;
        },
        
        handleDragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        },
        
        handleDrop(event, livewireComponent) {
            event.preventDefault();
            const itemId = event.dataTransfer.getData('text/plain');
            livewireComponent.updateItemStatus(itemId, this.dropColumn);
        }
    }));
});
