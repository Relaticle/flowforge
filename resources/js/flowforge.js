// Flowforge Kanban Board JavaScript

/**
 * Alpine.js component for Kanban Board functionality
 */
document.addEventListener('alpine:init', () => {
    Alpine.store('kanbanFilters', {
        searchTerm: '',
        showColumn: {},
        initialize(columns) {
            columns.forEach(column => {
                this.showColumn[column] = true;
            });
        },
        isColumnVisible(column) {
            return this.showColumn[column] || false;
        },
        toggleColumn(column) {
            this.showColumn[column] = !this.showColumn[column];
        }
    });

    Alpine.data('kanbanBoard', () => ({
        columns: {},
        columnCounts: {},
        draggedCardId: null,
        sourceColumn: null,
        loading: false,
        searchTerm: '',
        boardElement: null,
        
        /**
         * Initialize the kanban board
         */
        initialize() {
            this.boardElement = this.$el;
            this.initializeColumnData();
            this.setupEventListeners();
        },
        
        /**
         * Initialize column data from DOM
         */
        initializeColumnData() {
            const columnElements = this.boardElement.querySelectorAll('.flowforge-kanban-column');
            
            // Extract column values
            const columnValues = Array.from(columnElements).map(el => el.dataset.columnValue);
            
            // Initialize Alpine store
            Alpine.store('kanbanFilters').initialize(columnValues);
            
            // Initialize column counts
            columnElements.forEach(column => {
                const columnValue = column.dataset.columnValue;
                const cardsCount = column.querySelectorAll('.flowforge-card').length;
                this.columnCounts[columnValue] = cardsCount;
            });
        },
        
        /**
         * Setup event listeners for kanban board
         */
        setupEventListeners() {
            // Livewire events for refreshing data
            Livewire.on('kanban-items-loaded', () => {
                this.loading = false;
                this.initializeColumnData();
                this.filterCards();
            });
            
            Livewire.on('kanban-item-updated', ({ itemId, oldStatus, newStatus }) => {
                // Update column counts
                if (this.columnCounts[oldStatus]) {
                    this.columnCounts[oldStatus]--;
                }
                
                if (this.columnCounts[newStatus] !== undefined) {
                    this.columnCounts[newStatus]++;
                }
                
                // Flash animation on success
                this.flashSuccessAnimation(itemId);
            });
            
            Livewire.on('kanban-count-updated', ({ oldColumn, newColumn, oldCount, newCount }) => {
                this.columnCounts[oldColumn] = oldCount;
                this.columnCounts[newColumn] = newCount;
            });
            
            // Enhanced drag effect
            document.addEventListener('dragover', this.enhancedDragEffect.bind(this));
        },
        
        /**
         * Enhanced drag visual effect
         * @param {DragEvent} event 
         */
        enhancedDragEffect(event) {
            if (!this.draggedCardId) return;
            
            // Find all column elements
            const columns = document.querySelectorAll('.flowforge-kanban-column');
            
            // Add/remove drag-over class for visual feedback
            columns.forEach(column => {
                const rect = column.getBoundingClientRect();
                const isOver = (
                    event.clientX >= rect.left &&
                    event.clientX <= rect.right &&
                    event.clientY >= rect.top &&
                    event.clientY <= rect.bottom
                );
                
                column.classList.toggle('drag-over', isOver);
            });
        },
        
        /**
         * Get column count
         * @param {string} columnValue 
         * @returns {number}
         */
        getColumnCount(columnValue) {
            return this.columnCounts[columnValue] || 0;
        },
        
        /**
         * Check if column is empty
         * @param {string} columnValue 
         * @returns {boolean}
         */
        isColumnEmpty(columnValue) {
            const columnElement = document.getElementById(`column-${columnValue}`);
            if (!columnElement) return true;
            
            // Check visible cards
            const visibleCards = Array.from(columnElement.querySelectorAll('.flowforge-card'))
                .filter(card => !card.classList.contains('hidden'));
            
            return visibleCards.length === 0;
        },
        
        /**
         * Start dragging a card
         * @param {DragEvent} event 
         * @param {string} cardId 
         * @param {string} columnValue 
         */
        dragStart(event, cardId, columnValue) {
            this.draggedCardId = cardId;
            this.sourceColumn = columnValue;
            
            // Add dragging class
            event.target.classList.add('dragging');
            
            // Create custom drag image for better UX
            this.createDragImage(event);

            // Set data transfer
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', cardId);
        },
        
        /**
         * Create custom drag image
         * @param {DragEvent} event 
         */
        createDragImage(event) {
            const card = event.target;
            const rect = card.getBoundingClientRect();
            
            // Create a clone for drag image
            const clone = card.cloneNode(true);
            clone.style.width = `${rect.width}px`;
            clone.style.opacity = '0.8';
            clone.style.position = 'absolute';
            clone.style.top = '-1000px';
            clone.style.background = 'white';
            clone.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
            
            document.body.appendChild(clone);
            
            // Set drag image with offset
            event.dataTransfer.setDragImage(clone, rect.width / 2, 20);
            
            // Clean up clone after dragging
            setTimeout(() => {
                document.body.removeChild(clone);
            }, 0);
        },
        
        /**
         * End dragging a card
         * @param {DragEvent} event 
         */
        dragEnd(event) {
            // Remove dragging class
            event.target.classList.remove('dragging');
            
            // Remove drag-over class from all columns
            document.querySelectorAll('.flowforge-kanban-column').forEach(column => {
                column.classList.remove('drag-over');
            });
            
            // Clear drag data
            this.draggedCardId = null;
            this.sourceColumn = null;
        },
        
        /**
         * Handle card drop
         * @param {DragEvent} event 
         * @param {string} targetColumn 
         */
        dropCard(event, targetColumn) {
            event.preventDefault();
            
            // Get card ID from data transfer
            const cardId = event.dataTransfer.getData('text/plain') || this.draggedCardId;
            
            if (!cardId || !this.sourceColumn) return;
            
            // Don't do anything if dropping in the same column
            if (this.sourceColumn === targetColumn) return;
            
            // Show loading state
            this.loading = true;
            
            // Create visual placeholder for better UX
            this.createDropPlaceholder(event, targetColumn);
            
            // Call Livewire method to update item status
            this.$wire.updateItemStatus(cardId, targetColumn);
        },
        
        /**
         * Create visual placeholder when dropping
         * @param {DragEvent} event 
         * @param {string} targetColumn 
         */
        createDropPlaceholder(event, targetColumn) {
            const columnElement = document.getElementById(`column-${targetColumn}`);
            if (!columnElement) return;
            
            // Create placeholder element
            const placeholder = document.createElement('div');
            placeholder.className = 'flowforge-drop-placeholder';
            placeholder.setAttribute('data-placeholder-for', this.draggedCardId);
            
            // Add placeholder to column
            columnElement.appendChild(placeholder);
            
            // Remove placeholder after animation completes
            setTimeout(() => {
                if (placeholder.parentNode) {
                    placeholder.parentNode.removeChild(placeholder);
                }
            }, 1500);
        },
        
        /**
         * Add success animation to card
         * @param {string} cardId 
         */
        flashSuccessAnimation(cardId) {
            setTimeout(() => {
                const card = document.getElementById(`card-${cardId}`);
                if (!card) return;
                
                card.classList.add('flash-success');
                
                setTimeout(() => {
                    card.classList.remove('flash-success');
                }, 1000);
            }, 100);
        },
        
        /**
         * Filter cards based on search term
         */
        filterCards() {
            const searchTerm = this.searchTerm.toLowerCase().trim();
            
            // Get all cards
            const cards = document.querySelectorAll('.flowforge-card');
            
            cards.forEach(card => {
                const searchContent = card.dataset.searchContent || '';
                const isVisible = !searchTerm || searchContent.toLowerCase().includes(searchTerm);
                
                // Toggle visibility
                card.classList.toggle('hidden', !isVisible);
            });
            
            // Update empty states
            this.updateEmptyStates();
        },
        
        /**
         * Update empty state visibility
         */
        updateEmptyStates() {
            document.querySelectorAll('.flowforge-kanban-column').forEach(column => {
                const columnValue = column.dataset.columnValue;
                const empty = this.isColumnEmpty(columnValue);
                
                const emptyState = column.querySelector('.flowforge-empty-state');
                if (emptyState) {
                    emptyState.style.display = empty ? 'flex' : 'none';
                }
            });
        },
        
        /**
         * Refresh the board
         */
        refreshBoard() {
            // Show loading state
            this.loading = true;
            
            // Add refreshing animation to button
            const refreshBtn = this.boardElement.querySelector('.flowforge-refresh-btn');
            if (refreshBtn) {
                refreshBtn.classList.add('refreshing');
                
                setTimeout(() => {
                    refreshBtn.classList.remove('refreshing');
                }, 2000);
            }
            
            // Call Livewire refresh method
            this.$wire.loadItems();
        }
    }));
});

/**
 * Add keyboard navigation for improved accessibility
 */
document.addEventListener('DOMContentLoaded', () => {
    // Allow keyboard navigation for cards
    document.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
            const focused = document.activeElement;
            if (focused && focused.classList.contains('flowforge-card')) {
                // Toggle card selection
                focused.classList.toggle('selected');
            }
        }
    });
});

// Add CSS classes for different priority levels
document.addEventListener('kanban-items-loaded', () => {
    // Example: Add priority classes based on some attribute
    document.querySelectorAll('.flowforge-card').forEach(card => {
        const priorityElement = card.querySelector('[data-priority]');
        if (priorityElement) {
            const priority = priorityElement.dataset.priority;
            card.classList.add(`priority-${priority}`);
        }
    });
});
