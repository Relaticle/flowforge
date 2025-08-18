<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Illuminate\Support\Str;

trait HasStateFormatting
{
    protected Closure|null $formatStateUsing = null;

    protected bool|Closure $shouldFormatStateUsing = true;

    public function formatStateUsing(?Closure $callback): static
    {
        $this->formatStateUsing = $callback;

        return $this;
    }

    public function formatState(mixed $state): mixed
    {
        if ($this->formatStateUsing instanceof Closure) {
            return $this->evaluate($this->formatStateUsing, [
                'state' => $state,
            ]);
        }

        return $this->getDefaultFormattedState($state);
    }

    protected function getDefaultFormattedState(mixed $state): mixed
    {
        if (is_string($state)) {
            return Str::title($state);
        }

        if (is_bool($state)) {
            return $state ? 'Yes' : 'No';
        }

        if (is_array($state)) {
            return implode(', ', $state);
        }

        return $state;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'formatStateUsing' => [$this->formatStateUsing],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}