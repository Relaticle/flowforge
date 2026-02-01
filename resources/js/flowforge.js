export default function flowforge({state}) {
    return {
        state,
        isLoading: {},
        fullyLoaded: {},
        collapsedSwimlanes: {},

        init() {
            this.$wire.$on('kanban-items-loaded', (event) => {
                const { columnId, isFullyLoaded } = event;
                if (isFullyLoaded) {
                    this.fullyLoaded[columnId] = true;
                }
            });

            // Restore collapsed swimlane state from localStorage
            if (this.state.swimlanes) {
                this._restoreSwimlaneState();
            }
        },

        handleSortableEnd(event) {
            const newOrder = event.to.sortable.toArray();
            let cardId = event.item.getAttribute('x-sortable-item');

            // Fallback to data-card-id if x-sortable-item is missing (edge case safety)
            if (!cardId) {
                cardId = event.item.getAttribute('data-card-id');
                if (!cardId) {
                    console.error('Flowforge: Could not determine card ID for move operation');
                    return;
                }
            }

            const targetColumn = event.to.getAttribute('data-column-id');
            if (!targetColumn) {
                console.error('Flowforge: Target column ID is missing');
                return;
            }

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

        // --- Swimlane collapse/expand ---

        toggleSwimlane(swimlaneId) {
            this.collapsedSwimlanes[swimlaneId] = !this.collapsedSwimlanes[swimlaneId];
            this._saveSwimlaneState();
        },

        isSwimlaneCollapsed(swimlaneId) {
            return this.collapsedSwimlanes[swimlaneId] || false;
        },

        _getSwimlaneStorageKey() {
            // Use the page URL path as a board-specific key
            return 'flowforge:swimlanes:' + window.location.pathname;
        },

        _saveSwimlaneState() {
            try {
                localStorage.setItem(
                    this._getSwimlaneStorageKey(),
                    JSON.stringify(this.collapsedSwimlanes)
                );
            } catch (e) {
                // localStorage may be unavailable; ignore silently
            }
        },

        _restoreSwimlaneState() {
            try {
                const stored = localStorage.getItem(this._getSwimlaneStorageKey());
                if (stored) {
                    this.collapsedSwimlanes = JSON.parse(stored);
                }
            } catch (e) {
                this.collapsedSwimlanes = {};
            }
        },
    }
}
