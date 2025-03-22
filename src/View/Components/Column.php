<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\View\Components;

use Illuminate\View\Component;

class Column extends Component
{
    /**
     * Create a new component instance.
     *
     * @param string $id     The ID of the column
     * @param string $name   The name of the column
     * @param array  $items  The items in the column
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $items = []
    ) {}

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('flowforge::components.column');
    }
}
