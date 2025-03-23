export default function flowforge({state}) {
    return {
        state,
        currentColumn: null,
        currentCard: null,
        columns: state.columns,
        formData: {},

        init: function () {
            console.log('FlowForge Alpine component initialized')
            // Initialize any event listeners or plugins
            // Listen for form submission success
            this.$wire.$on('card-created', () => {
                this.showSuccessNotification('Card created successfully');
            });

            this.$wire.$on('card-updated', () => {
                this.showSuccessNotification('Card updated successfully');
            });

            this.$wire.$on('card-deleted', () => {
                this.showSuccessNotification('Card deleted successfully');
            });
        },

        openCreateModal(columnId) {
            // Reset form data
            this.formData = {
                [state.statusField]: columnId,
                title: '',
                description: ''
            };
            this.currentColumn = columnId;

            this.$dispatch('open-modal', { id: 'create-card-modal' });

            // Focus title field when modal opens
            setTimeout(() => {
                const titleInput = document.querySelector('#create-card-modal input[type="text"]');
                if (titleInput) titleInput.focus();
            }, 100);
        },

        submitCreateForm() {
            if (!this.formData.title) {
                return this.showErrorNotification('Title is required');
            }

            this.$wire.createCard(this.formData).then(cardId => {
                if (cardId) {
                    this.$dispatch('close-modal', { id: 'create-card-modal' });
                    this.formData = {};

                    // Highlight the new card
                    setTimeout(() => {
                        const cardElement = document.querySelector(`[x-sortable-item="${cardId}"]`);
                        if (cardElement) {
                            cardElement.classList.add('animate-kanban-card-add');
                            setTimeout(() => {
                                cardElement.classList.remove('animate-kanban-card-add');
                            }, 500);
                        }
                    }, 100);
                }
            }).catch(error => {
                this.showErrorNotification('Failed to create card');
                console.error('Error creating card:', error);
            });
        },

        openEditModal(card, columnId) {
            this.currentCard = card;
            this.currentColumn = columnId;

            // Initialize form data with current card values
            this.formData = {...card};
            this.formData[state.statusField] = columnId;

            this.$dispatch('open-modal', { id: 'edit-card-modal' });

            // Focus title field when modal opens
            setTimeout(() => {
                const titleInput = document.querySelector('#edit-card-modal input[type="text"]');
                if (titleInput) titleInput.focus();
            }, 100);
        },

        submitEditForm() {
            if (!this.formData.title) {
                return this.showErrorNotification('Title is required');
            }

            this.$wire.updateCard(this.currentCard.id, this.formData).then(result => {
                if (result) {
                    this.$dispatch('close-modal', { id: 'edit-card-modal' });
                    this.formData = {};

                    // Highlight the updated card
                    setTimeout(() => {
                        const cardElement = document.querySelector(`[x-sortable-item="${this.currentCard.id}"]`);
                        if (cardElement) {
                            cardElement.classList.add('animate-kanban-card-move');
                            setTimeout(() => {
                                cardElement.classList.remove('animate-kanban-card-move');
                            }, 500);
                        }
                    }, 100);
                }
            }).catch(error => {
                this.showErrorNotification('Failed to update card');
                console.error('Error updating card:', error);
            });
        },

        deleteCard() {
            if (confirm('Are you sure you want to delete this card? This action cannot be undone.')) {
                this.$wire.deleteCard(this.currentCard.id).then(result => {
                    if (result) {
                        this.$dispatch('close-modal', { id: 'edit-card-modal' });
                        this.formData = {};
                    }
                }).catch(error => {
                    this.showErrorNotification('Failed to delete card');
                    console.error('Error deleting card:', error);
                });
            }
        },

        showSuccessNotification(message) {
            // Trigger Filament notification if available
            if (window.Filament && window.Filament.notify) {
                window.Filament.notify.success(message);
            } else {
                console.log('Success:', message);
            }
        },

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
