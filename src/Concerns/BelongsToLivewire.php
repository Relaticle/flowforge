<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Relaticle\Flowforge\Contracts\HasBoard;

trait BelongsToLivewire
{
    protected HasBoard $livewire;

    public function livewire(HasBoard $livewire): static
    {
        $this->livewire = $livewire;

        return $this;
    }

    public function getLivewire(): HasBoard
    {
        return $this->livewire;
    }
}
