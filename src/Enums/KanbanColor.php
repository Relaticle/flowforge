<?php

namespace Relaticle\Flowforge\Enums;

enum KanbanColor: string
{
    case DEFAULT = 'default';
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

    /**
     * Get the display name for this color
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get all available colors as options array for select fields
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($color) => [
            $color->value => $color->label(),
        ])->toArray();
    }

    /**
     * Create from string value with default fallback
     */
    public static function fromStringOrDefault(?string $value): self
    {
        if ($value === null) {
            return self::DEFAULT;
        }

        return self::tryFrom($value) ?? self::DEFAULT;
    }
}
