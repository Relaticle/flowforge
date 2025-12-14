export default function flowforge({state}) {
    return {
        state,
        isLoading: {},
        fullyLoaded: {},

        init() {
            this.$wire.$on('kanban-items-loaded', (event) => {
                const { columnId, isFullyLoaded } = event;
                if (isFullyLoaded) {
                    this.fullyLoaded[columnId] = true;
                }
            });
        },

        handleSortableEnd(event) {
            const newOrder = event.to.sortable.toArray();
            const cardId = event.item.getAttribute('x-sortable-item');
            const targetColumn = event.to.getAttribute('data-column-id');
            const cardElement = event.item;

            this.setCardState(cardElement, true);

            const cardIndex = newOrder.indexOf(cardId);
            const afterCardId = cardIndex > 0 ? newOrder[cardIndex - 1] : null;
            const beforeCardId = cardIndex < newOrder.length - 1 ? newOrder[cardIndex + 1] : null;

            this.$wire.moveCard(cardId, targetColumn, afterCardId, beforeCardId)
                .then(() => this.setCardState(cardElement, false))
                .catch(() => this.setCardState(cardElement, false));
        },

        setCardState(cardElement, disabled) {
            cardElement.style.opacity = disabled ? '0.7' : '';
            cardElement.style.pointerEvents = disabled ? 'none' : '';
        },

        isLoadingColumn(columnId) {
            return this.isLoading[columnId] || false;
        },

        isColumnFullyLoaded(columnId) {
            return this.fullyLoaded[columnId] || false;
        },

        handleSmoothScroll(columnId) {
            if (this.isLoadingColumn(columnId) || this.isColumnFullyLoaded(columnId)) {
                return;
            }

            this.isLoading[columnId] = true;

            this.$wire.loadMoreItems(columnId)
                .then(() => setTimeout(() => this.isLoading[columnId] = false, 100))
                .catch(() => this.isLoading[columnId] = false);
        },

        handleColumnScroll(event, columnId) {
            if (this.isColumnFullyLoaded(columnId)) return;

            const { scrollTop, scrollHeight, clientHeight } = event.target;
            const scrollPercentage = (scrollTop + clientHeight) / scrollHeight;

            if (scrollPercentage >= 0.8 && !this.isLoadingColumn(columnId)) {
                this.handleSmoothScroll(columnId);
            }
        },
    }
}
