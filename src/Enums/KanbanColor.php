<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KanbanColor: string implements HasColor, HasLabel
{
    case DEFAULT = 'default';
    case WHITE = 'white';
    case SLATE = 'slate';
    case GRAY = 'gray';
    case ZINC = 'zinc';
    case NEUTRAL = 'neutral';
    case STONE = 'stone';
    case RED = 'red';
    case ORANGE = 'orange';
    case AMBER = 'amber';
    case YELLOW = 'yellow';
    case LIME = 'lime';
    case GREEN = 'green';
    case EMERALD = 'emerald';
    case TEAL = 'teal';
    case CYAN = 'cyan';
    case SKY = 'sky';
    case BLUE = 'blue';
    case INDIGO = 'indigo';
    case VIOLET = 'violet';
    case PURPLE = 'purple';
    case FUCHSIA = 'fuchsia';
    case PINK = 'pink';
    case ROSE = 'rose';

    /**
     * Get the CSS class for this color
     */
    public function classes(): string
    {
        return 'kanban-color-' . $this->value;
    }

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string | array | null
    {
        return $this->value;
    }

    /**
     * Get all available colors as options array for select fields
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($color) => [
            $color->value => $color->getLabel(),
        ])->toArray();
    }
}
