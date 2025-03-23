export default function flowforge({state}) {
    return {
        state,
        columns: state.columns,
        currentColumn: null,
        currentCard: null,
        formData: {},

        init: function () {
            console.log('FlowForge Alpine component initialized')

            // Listen for form submission success/failure
            this.$wire.$on('kanban-card-created', (data) => {
                const id = data[0].id;
                const status = data[0].status;

                this.$dispatch('close-modal', { id: 'create-card-modal' });

                // Highlight the new card
                setTimeout(() => {
                    const cardElement = document.querySelector(`[x-sortable-item="${id}"]`);
                    if (cardElement) {
                        cardElement.classList.add('animate-kanban-card-add');
                        setTimeout(() => {
                            cardElement.classList.remove('animate-kanban-card-add');
                        }, 500);
                    }
                }, 300);
            });

            this.$wire.$on('kanban-card-updated', (data) => {

                const id = data[0].id;

                this.$dispatch('close-modal', { id: 'edit-card-modal' });

                // Highlight the updated card
                setTimeout(() => {
                    const cardElement = document.querySelector(`[x-sortable-item="${id}"]`);
                    if (cardElement) {
                        cardElement.classList.add('animate-kanban-card-move');
                        setTimeout(() => {
                            cardElement.classList.remove('animate-kanban-card-move');
                        }, 500);
                    }
                }, 300);
            });

            this.$wire.$on('kanban-card-deleted', (data) => {
                const id = data[0].id;

                this.$dispatch('close-modal', { id: 'edit-card-modal' });

                // Highlight the deleted card
                setTimeout(() => {
                    const cardElement = document.querySelector(`[x-sortable-item="${id}"]`);
                    if (cardElement) {
                        cardElement.classList.add('animate-kanban-card-delete');
                        setTimeout(() => {
                            cardElement.classList.remove('animate-kanban-card-delete');
                        }, 500);
                    }
                }, 300);
            })

            // Listen for when items are loaded
            this.$wire.$on('kanban-items-loaded', () => {
                // Initialize sortable for newly loaded items
                this.initSortable();
            });
        },

        initSortable() {
            // Re-initialize sortable for all columns if needed
            // This could be implemented if needed for dynamically loaded content
        },

        /**
         * Helper function for success notifications (maintained for backward compatibility)
         */
        showSuccessNotification(message) {
            // Trigger Filament notification if available
            if (window.Filament && window.Filament.notify) {
                window.Filament.notify.success(message);
            } else {
                console.log('Success:', message);
            }
        },

        /**
         * Helper function for error notifications (maintained for backward compatibility)
         */
        showErrorNotification(message) {
            // Trigger Filament notification if available
            if (window.Filament && window.Filament.notify) {
                window.Filament.notify.error(message);
            } else {
                console.error('Error:', message);
            }
        }
    }
}
