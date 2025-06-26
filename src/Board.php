<?php

namespace Relaticle\Flowforge;

use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Concerns\HasColumns;
use Relaticle\Flowforge\Concerns\HasActions;
use Relaticle\Flowforge\Concerns\HasProperties;

class Board extends ViewComponent
{
    use HasColumns;
    use HasActions;
    use HasProperties;

    /**
     * @var view-string
     */
    protected string $view = 'flowforge::board';

    protected string $viewIdentifier = 'board';

    protected string $evaluationIdentifier = 'board';

    protected ?string $recordTitleAttribute = null;

    protected ?string $columnIdentifierAttribute = null;

    protected ?string $descriptionAttribute = null;

    protected ?array $defaultSort = null;

    protected array $filters = [];

    public function recordTitleAttribute(string $attribute): static
    {
        $this->recordTitleAttribute = $attribute;

        return $this;
    }

    public function columnIdentifierAttribute(string $attribute): static
    {
        $this->columnIdentifierAttribute = $attribute;

        return $this;
    }

    public function descriptionAttribute(string $attribute): static
    {
        $this->descriptionAttribute = $attribute;

        return $this;
    }

    public function defaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSort = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * @param array $filters
     */
    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function getRecordTitleAttribute(): ?string
    {
        return $this->recordTitleAttribute;
    }

    public function getColumnIdentifierAttribute(): ?string
    {
        return $this->columnIdentifierAttribute;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->descriptionAttribute;
    }

    public function getDefaultSort(): ?array
    {
        return $this->defaultSort;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'board' => [$this],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Any board-specific setup can go here
    }
}
