export default function flowforge({state}) {
    return {
        state,
        columns: state.columns,
        currentColumn: null,
        isLoading: {},

        init: function () {},

        /**
         * Handle x-sortable end event with enterprise positioning
         */
        handleSortableEnd(event) {
            // Get x-sortable's calculated order
            const newOrder = event.to.sortable.toArray();
            const cardId = event.item.getAttribute('x-sortable-item');
            const targetColumn = event.to.getAttribute('data-column-id');

            // Add visual feedback during enterprise processing
            const cardElement = event.item;
            cardElement.style.opacity = '0.7';
            cardElement.style.pointerEvents = 'none';

            // Convert x-sortable order to enterprise positioning
            const cardIndex = newOrder.indexOf(cardId);
            const afterCardId = cardIndex > 0 ? newOrder[cardIndex - 1] : null;
            const beforeCardId = cardIndex < newOrder.length - 1 ? newOrder[cardIndex + 1] : null;

            // Debug logging
            console.log('Flowforge Debug:', {
                cardId,
                targetColumn,
                cardIndex,
                newOrder,
                afterCardId,
                beforeCardId
            });

            // Send to enterprise backend (this will trigger Livewire re-render when complete)
            this.$wire.moveCard(cardId, targetColumn, beforeCardId, afterCardId).then(() => {
                // Re-enable card after successful move
                cardElement.style.opacity = '';
                cardElement.style.pointerEvents = '';
            }).catch(() => {
                // Re-enable card after failed move
                cardElement.style.opacity = '';
                cardElement.style.pointerEvents = '';
            });
        },

        /**
         * Handle successful move confirmation
         */
        handleMoveSuccess(cardId, columnId, position) {
            const cardElement = document.querySelector(`[x-sortable-item="${cardId}"]`);
            if (cardElement) {
                cardElement.classList.add('animate-kanban-card-success');
                setTimeout(() => {
                    cardElement.classList.remove('animate-kanban-card-success');
                }, 500);
            }
        },

        /**
         * Handle move failure with rollback
         */
        handleMoveFailure(cardId, message) {
            // Show error notification
            this.$dispatch('kanban-error', { message });

            const cardElement = document.querySelector(`[x-sortable-item="${cardId}"]`);
            if (cardElement) {
                cardElement.classList.add('animate-kanban-card-error');
                setTimeout(() => {
                    cardElement.classList.remove('animate-kanban-card-error');
                }, 500);
            }
        },

        /**
         * Animate card addition
         */
        animateCardAdd(cardId) {
            setTimeout(() => {
                const cardElement = document.querySelector(`[x-sortable-item="${cardId}"]`);
                if (cardElement) {
                    cardElement.classList.add('animate-kanban-card-add');
                    setTimeout(() => {
                        cardElement.classList.remove('animate-kanban-card-add');
                    }, 500);
                }
            }, 300);
        },

        /**
         * Animate card deletion
         */
        animateCardDelete(cardId) {
            setTimeout(() => {
                const cardElement = document.querySelector(`[x-sortable-item="${cardId}"]`);
                if (cardElement) {
                    cardElement.classList.add('animate-kanban-card-delete');
                    setTimeout(() => {
                        cardElement.classList.remove('animate-kanban-card-delete');
                    }, 500);
                }
            }, 300);
        },

        /**
         * Check if we're loading items for a specific column
         */
        isLoadingColumn(columnId) {
            return this.isLoading[columnId] || false;
        },

        /**
         * Begin loading more items for a column
         */
        beginLoading(columnId) {
            this.isLoading[columnId] = true;
        },
    }
}
