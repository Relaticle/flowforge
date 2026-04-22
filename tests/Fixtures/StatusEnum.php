<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Pending = 'pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active Item',
            self::Pending => 'Pending Item',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Active => 'heroicon-o-check',
            self::Pending => 'heroicon-o-clock',
        };
    }
}
