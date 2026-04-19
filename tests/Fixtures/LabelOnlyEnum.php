<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Support\Contracts\HasLabel;

enum LabelOnlyEnum: string implements HasLabel
{
    case Draft = 'draft';

    public function getLabel(): string
    {
        return 'Draft Item';
    }
}
