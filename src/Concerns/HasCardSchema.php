<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

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
     * Get the configured card schema for a specific record.
     */
    public function getCardSchema(Model $record): ?Schema
    {
        if ($this->cardSchemaBuilder === null) {
            return null;
        }

        $livewire = $this->getLivewire();
        /** @phpstan-ignore argument.type (Filament Schema expects HasSchemas&Livewire\Component but getLivewire returns HasBoard) */
        $schema = Schema::make($livewire)->record($record);

        return $this->evaluate($this->cardSchemaBuilder, ['schema' => $schema]);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        /** @phpstan-ignore argument.type (Filament Schema expects HasSchemas&Livewire\Component but getLivewire returns HasBoard) */
        return match ($parameterName) {
            'schema' => [Schema::make($this->getLivewire())],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
