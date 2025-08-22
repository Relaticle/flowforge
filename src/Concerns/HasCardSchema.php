<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Schemas\Schema;

trait HasCardSchema
{
    protected ?Closure $cardSchemaBuilder = null;

    /**
     * Configure the card schema using the Schema builder pattern.
     */
    public function cardSchema(Closure $builder): static
    {
        $this->cardSchemaBuilder = $builder;

        return $this;
    }

    /**
     * Get the configured card schema.
     */
    public function getCardSchema(): ?Schema
    {
        if ($this->cardSchemaBuilder === null) {
            return null;
        }

        $livewire = $this->getLivewire();
        $schema = Schema::make($livewire);
        
        return $this->evaluate($this->cardSchemaBuilder, ['schema' => $schema]);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'schema' => [Schema::make($this->getLivewire())],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
