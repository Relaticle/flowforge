<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class CardFlex extends Component
{
    protected string $view = 'flowforge::components.card-flex';

    protected string $gap = 'xs';

    protected bool $wrap = true;

    protected string $justify = 'start';

    protected string $align = 'center';

    /**
     * @param  array<Component | Action | ActionGroup | string | Htmlable> | Closure  $schema
     */
    final public function __construct(array | Closure $schema)
    {
        $this->schema($schema);
    }

    /**
     * @param  array<Component | Action | ActionGroup | string | Htmlable> | Closure  $schema
     */
    public static function make(array | Closure $schema): static
    {
        $static = app(static::class, ['schema' => $schema]);
        $static->configure();

        return $static;
    }

    /**
     * Enable/disable wrapping on smaller screens.
     */
    public function wrap(bool $wrap = true): static
    {
        $this->wrap = $wrap;

        return $this;
    }

    /**
     * Set horizontal justification.
     */
    public function justify(string $justify): static
    {
        $this->justify = $justify;

        return $this;
    }

    /**
     * Set vertical alignment.
     */
    public function align(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    /**
     * Get the gap size.
     */
    public function getGap(): string
    {
        return $this->gap;
    }

    /**
     * Check if wrapping is enabled.
     */
    public function shouldWrap(): bool
    {
        return $this->wrap;
    }

    /**
     * Get justification setting.
     */
    public function getJustify(): string
    {
        return $this->justify;
    }

    /**
     * Get alignment setting.
     */
    public function getAlign(): string
    {
        return $this->align;
    }
}
