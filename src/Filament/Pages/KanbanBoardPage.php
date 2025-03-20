<?php

namespace Relaticle\Flowforge\Filament\Pages;

use Filament\Pages\Page;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class KanbanBoardPage extends Page
{
    protected static string $view = 'flowforge::filament.pages.kanban-board-page';

    /**
     * @var IKanbanAdapter
     */
    protected IKanbanAdapter $adapter;

    /**
     * Set the Kanban adapter for this page.
     *
     * @param IKanbanAdapter $adapter
     * @return static
     */
    public function adapter(IKanbanAdapter $adapter): static
    {
        $this->adapter = $adapter;
        
        return $this;
    }

    /**
     * Get the Kanban adapter.
     *
     * @return IKanbanAdapter
     */
    public function getAdapter(): IKanbanAdapter
    {
        return $this->adapter;
    }

    /**
     * Mount the page.
     *
     * @return void
     */
    public function mount(): void
    {
        if (!isset($this->adapter)) {
            throw new \Exception('Kanban adapter not set. Use the adapter() method to set the adapter.');
        }
    }
}
