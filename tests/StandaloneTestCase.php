<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentManager;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Relaticle\Flowforge\FlowforgeServiceProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

/**
 * Test case for standalone Livewire usage WITHOUT the Filament Panel Builder.
 *
 * Excludes FilamentServiceProvider and TestPanelProvider to verify the package
 * works without a panel registered.
 *
 * Note: In a real standalone install (without filament/filament), the Filament
 * facade class won't exist and class_exists() guards in filament/tables skip
 * panel-dependent calls entirely. In our test environment, filament/filament IS
 * installed as a dev dependency, so we register a minimal FilamentManager binding
 * to satisfy those guards without configuring any panel.
 */
class StandaloneTestCase extends Orchestra
{
    use LazilyRefreshDatabase;
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Relaticle\\Flowforge\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        // Register minimal FilamentManager binding. This is only needed because
        // filament/filament is a dev dependency (for panel tests), making the
        // Filament facade class available. The tables package's HasFilters trait
        // uses class_exists(Filament::class) to guard tenant-aware session keys,
        // which passes in our test env but would be false in a real standalone install.
        if (! $this->app->bound('filament')) {
            $this->app->scoped('filament', fn () => new FilamentManager);
        }
    }

    protected function getPackageProviders($app): array
    {
        $providers = [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            FlowforgeServiceProvider::class,
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

        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        config()->set('session.driver', 'array');
        config()->set('session.encrypt', false);

        config()->set('view.paths', [
            resource_path('views'),
            __DIR__ . '/../resources/views',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
