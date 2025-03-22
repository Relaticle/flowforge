export default function flowforge({state}) {
    return {
        state,
        currentColumn: null,
        currentCard: null,
        formData: {
            a: '23',
        },

        init: function () {
            console.log('FlowForge Alpine component initialized')
            // Initialise the Alpine component here, if you need to.
        },

        openCreateModal(columnId) {
            this.currentColumn = columnId;
            this.formData = {
            [state.statusField]: columnId
        };
            this.$dispatch('open-modal', { id: 'create-card-modal' })
        },

        submitCreateForm() {
            console.log(this.formData);
            this.$wire.createCard(this.formData).then(cardId => {
                if (cardId) {
                    this.$dispatch('close-modal', { id: 'create-card-modal' });
                    this.formData = {};
                }
            });
        },

        openEditModal(card, columnId) {
            this.currentCard = card;
            this.currentColumn = columnId;

            // Initialize form data with current card values
            this.formData = {...card};
            this.formData[state.statusField] = columnId;

            this.$dispatch('open-modal', { id: 'edit-card-modal' })
        },

        submitEditForm() {
            this.$wire.updateCard(this.currentCard.id, this.formData).then(result => {
                if (result) {
                    this.$dispatch('close-modal', { id: 'edit-card-modal' });
                    this.formData = {};
                }
            });
        },

        deleteCard() {
            if (confirm('Are you sure you want to delete this card? This action cannot be undone.')) {
                this.$wire.deleteCard(this.currentCard.id).then(result => {
                    if (result) {
                        this.$dispatch('close-modal', { id: 'delete-card-modal' });
                        this.formData = {};
                    }
                });
            }
        },
    }
}
