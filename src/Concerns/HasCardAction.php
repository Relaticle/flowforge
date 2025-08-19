<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

trait HasCardAction
{
    protected string | Closure | null $cardAction = null;

    public function cardAction(string | Closure | null $action): static
    {
        $this->cardAction = $action;

        return $this;
    }

    /**
     * @param  Model | array<string, mixed>  $record
     */
    public function getCardAction(Model | array $record): ?string
    {
        $action = $this->evaluate(
            $this->cardAction,
            namedInjections: [
                'record' => $record,
            ],
            typedInjections: $record instanceof Model ? [
                Model::class => $record,
                $record::class => $record,
            ] : [],
        );

        if (! $action) {
            return null;
        }

        if (! class_exists($action)) {
            return $action;
        }

        if (! is_subclass_of($action, Action::class)) {
            return $action;
        }

        return $action::getDefaultName() ?? $action;
    }
}