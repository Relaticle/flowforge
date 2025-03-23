<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\View\Components;

use Illuminate\View\Component;

class Card extends Component
{
    /**
     * Create a new component instance.
     *
     * @param mixed  $id           The ID of the card
     * @param string $title        The title of the card
     * @param string|null $description The description of the card
     * @param array  $attributes   Additional attributes for the card
     * @param string|null $priority The priority of the card (high, medium, low)
     */
    public function __construct(
        public mixed $id,
        public string $title,
        public ?string $description = null,
        public array $attributes = [],
        public ?string $priority = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('flowforge::components.card');
    }

    /**
     * Get the priority border class.
     *
     * @return string
     */
    public function getPriorityClass(): string
    {
        if (!$this->priority) {
            return 'border-l-4 border-gray-300 dark:border-gray-600';
        }

        return match (strtolower($this->priority)) {
            'high' => 'border-l-4 border-red-500',
            'medium' => 'border-l-4 border-yellow-500',
            'low' => 'border-l-4 border-green-500',
            default => 'border-l-4 border-gray-300 dark:border-gray-600',
        };
    }
}
