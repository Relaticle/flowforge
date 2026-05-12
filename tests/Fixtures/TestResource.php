<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Resources\Resource;

class TestResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static bool $shouldCheckPolicyExistence = false;

    protected static bool $shouldSkipAuthorization = true;

    public static function getPages(): array
    {
        return [
            'index' => TestBoardResourcePage::route('/'),
        ];
    }
}
