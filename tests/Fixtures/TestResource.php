<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Resources\Resource;

/**
 * Minimal test resource for TestBoardResourcePage.
 */
class TestResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $slug = 'test-projects';

    public static function getPages(): array
    {
        return [
            'board' => TestBoardResourcePage::route('/{record}/board'),
        ];
    }
}
