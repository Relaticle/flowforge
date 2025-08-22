<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Relaticle\Flowforge\FlowforgeServiceProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Relaticle\\Flowforge\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

    }

    protected function getPackageProviders($app): array
    {
        $providers = [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FlowforgeServiceProvider::class,
            \Relaticle\Flowforge\Tests\Fixtures\TestPanelProvider::class,
        ];

        sort($providers);

        return $providers;
    }

    protected function defineEnvironment($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up app key for session encryption
        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // Configure session for Livewire
        config()->set('session.driver', 'array');
        config()->set('session.encrypt', false);

        // Configure view for testing
        config()->set('view.paths', [
            resource_path('views'),
            __DIR__ . '/../resources/views',
        ]);

        // Configure Filament for testing
        config()->set('filament.default_filesystem_disk', 'local');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
