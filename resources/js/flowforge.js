export default function flowforge({state}) {
    return {
        state,
        columns: state.columns,
        currentColumn: null,
        isLoading: {},

        init: function () {
            // Listen for card creation
            this.$wire.$on('kanban-record-created', (data) => {
                const id = data[0].id;
                const column = data[0].column;

                this.$dispatch('close-modal', { id: 'create-record-modal' });

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

            // Listen for card update
            this.$wire.$on('kanban-record-updated', (data) => {
                // const recordData = data[0].record;
                // const id = recordData.id;
                // const titleField = this.state.titleField;
                // const descriptionField = this.state.descriptionField;
            });

            // Listen for card deletion
            this.$wire.$on('kanban-record-deleted', (data) => {
                const id = data[0].id;

                this.$dispatch('close-modal', { id: 'edit-record-modal' });

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
            this.$wire.$on('kanban-items-loaded', (data) => {
                // Get the specific column that was updated
                const columnId = data[0]?.columnId;

                // Clear loading state for this column
                if (columnId) {
                    this.isLoading[columnId] = false;
                }
            });

            this.$wire.$on('close-modal', (data) => {
                // this.$wire.resetEditForm()
            })
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
