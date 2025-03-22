// export default function flowforge(config) {
//     return  {
//         columns: {},
//         isDragging: false,
//         draggedCard: null,
//         draggedCardClone: null,
//         sourceColumn: null,
//         hoverColumn: null,
//         config: config,
//         dropPlaceholderVisible: false,
//         dropPlaceholderColumn: null,
//         dropPlaceholderPosition: 0,
//
//         init() {
//             this.initColumns();
//             this.setupEventListeners();
//         },
//
//         initColumns() {
//             // Initialize column data from the server-side data
//             Object.keys(this.config.statusValues).forEach(status => {
//                 this.columns[status] = {
//                     id: status,
//                     name: this.config.statusValues[status],
//                     items: [],
//                     isOver: false
//                 };
//             });
//         },
//
//         setupEventListeners() {
//             // Listen for the card moved event from Livewire
//             this.$wire.$on('kanban-card-moved', data => {
//                 this.refreshBoard();
//             });
//         },
//
//         refreshBoard() {
//             // Refresh the board data
//             Object.keys(this.columns).forEach(status => {
//                 this.$wire.getItemsForStatus(status).then(items => {
//                     this.columns[status].items = items;
//                 });
//             });
//         },
//
//         /**
//          * Start dragging a card
//          */
//         startDrag(event, card, columnId) {
//             this.isDragging = true;
//             this.draggedCard = card;
//             this.sourceColumn = columnId;
//
//             // Create a clone of the card for the drag image
//             const original = event.target.closest('.kanban-card');
//             this.draggedCardClone = original.cloneNode(true);
//
//             // Style the clone for dragging
//             Object.assign(this.draggedCardClone.style, {
//                 position: 'absolute',
//                 top: '-1000px',
//                 left: '-1000px',
//                 width: `${original.offsetWidth}px`,
//                 height: `${original.offsetHeight}px`,
//                 opacity: '0.7',
//                 transform: 'rotate(2deg)',
//                 pointerEvents: 'none',
//                 zIndex: '1000',
//             });
//
//             document.body.appendChild(this.draggedCardClone);
//             event.dataTransfer.setDragImage(this.draggedCardClone, 20, 20);
//
//             // Set data for the drag operation
//             event.dataTransfer.setData('text/plain', JSON.stringify({
//                 id: card.id,
//                 columnId: columnId
//             }));
//
//             // Add classes for styling
//             original.classList.add('opacity-50', 'cursor-grabbing');
//         },
//
//         /**
//          * End dragging a card
//          */
//         endDrag(event) {
//             if (!this.isDragging) return;
//
//             // Remove the drag clone
//             if (this.draggedCardClone && this.draggedCardClone.parentNode) {
//                 this.draggedCardClone.parentNode.removeChild(this.draggedCardClone);
//             }
//
//             // Reset drag state
//             this.isDragging = false;
//             this.draggedCard = null;
//             this.draggedCardClone = null;
//             this.sourceColumn = null;
//             this.hoverColumn = null;
//             this.dropPlaceholderVisible = false;
//
//             // Remove drag classes from all cards
//             document.querySelectorAll('.kanban-card').forEach(card => {
//                 card.classList.remove('opacity-50', 'cursor-grabbing');
//             });
//         },
//
//         /**
//          * Handle drag over on a column
//          */
//         dragOver(event, columnId) {
//             event.preventDefault();
//             this.hoverColumn = columnId;
//
//             // Show drop placeholder
//             const column = event.currentTarget;
//             const columnRect = column.getBoundingClientRect();
//             const relativeY = event.clientY - columnRect.top;
//
//             // Find the position to insert the placeholder
//             const cards = Array.from(column.querySelectorAll('.kanban-card:not(.opacity-50)'));
//             let position = cards.length;
//
//             for (let i = 0; i < cards.length; i++) {
//                 const card = cards[i];
//                 const cardRect = card.getBoundingClientRect();
//                 const cardMiddle = cardRect.top + cardRect.height / 2;
//
//                 if (event.clientY < cardMiddle) {
//                     position = i;
//                     break;
//                 }
//             }
//
//             this.dropPlaceholderVisible = true;
//             this.dropPlaceholderColumn = columnId;
//             this.dropPlaceholderPosition = position;
//         },
//
//         /**
//          * Handle drag leave on a column
//          */
//         dragLeave(event) {
//             // Hide drop placeholder when leaving a column
//             if (!event.relatedTarget || !event.currentTarget.contains(event.relatedTarget)) {
//                 this.dropPlaceholderVisible = false;
//                 this.hoverColumn = null;
//             }
//         },
//
//         /**
//          * Handle drop on a column
//          */
//         drop(event, columnId) {
//             event.preventDefault();
//
//             // Hide drop placeholder
//             this.dropPlaceholderVisible = false;
//
//             // Get the data from the drag operation
//             const data = JSON.parse(event.dataTransfer.getData('text/plain'));
//
//             // Don't do anything if dropping back to the same column
//             if (data.columnId === columnId) return;
//
//             // Update the card status via Livewire
//             if (this.draggedCard) {
//                 this.$wire.updateStatus(this.draggedCard.id, columnId)
//                     .then(() => {
//                         // Show success animation
//                         const droppedCard = document.querySelector(`[data-id="${this.draggedCard.id}"]`);
//                         if (droppedCard) {
//                             droppedCard.classList.add('animate-success');
//                             setTimeout(() => {
//                                 droppedCard.classList.remove('animate-success');
//                             }, 1000);
//                         }
//                     })
//                     .catch(() => {
//                         // Show error animation
//                         const droppedCard = document.querySelector(`[data-id="${this.draggedCard.id}"]`);
//                         if (droppedCard) {
//                             droppedCard.classList.add('animate-error');
//                             setTimeout(() => {
//                                 droppedCard.classList.remove('animate-error');
//                             }, 1000);
//                         }
//                     });
//             }
//         },
//
//         /**
//          * Determine if a drop placeholder should be visible
//          */
//         isDropPlaceholderVisible(columnId, position) {
//             return this.dropPlaceholderVisible &&
//                 this.dropPlaceholderColumn === columnId &&
//                 this.dropPlaceholderPosition === position;
//         },
//
//         /**
//          * Generate card style classes based on card data
//          */
//         getCardClasses(card) {
//             const classes = ['kanban-card'];
//
//             // Add priority classes if available
//             if (card.priority) {
//                 switch(card.priority.toLowerCase()) {
//                     case 'high':
//                         classes.push('border-l-4 border-red-500');
//                         break;
//                     case 'medium':
//                         classes.push('border-l-4 border-yellow-500');
//                         break;
//                     case 'low':
//                         classes.push('border-l-4 border-green-500');
//                         break;
//                     default:
//                         classes.push('border-l-4 border-gray-300');
//                 }
//             }
//
//             return classes.join(' ');
//         },
//
//         /**
//          * Get the filtered items for a column
//          */
//         getFilteredItems(columnId) {
//             const store = Alpine.store('kanbanFilter');
//             return this.columns[columnId].items.filter(card =>
//                 store.filterCard(card.title)
//             );
//         },
//
//         /**
//          * Search the board
//          */
//         search(query) {
//             Alpine.store('kanbanFilter').searchQuery = query;
//         }
//     }
// }
