<?php

namespace Relaticle\Flowforge;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Relaticle\Flowforge\Commands\FlowforgeCommand;
use Relaticle\Flowforge\Livewire\KanbanBoard;
use Relaticle\Flowforge\Testing\TestsFlowforge;

class FlowforgeServiceProvider extends PackageServiceProvider
{
    public static string $name = 'flowforge';

    public static string $viewNamespace = 'flowforge';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('relaticle/flowforge');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/flowforge/{$file->getFilename()}"),
                ], 'flowforge-stubs');
            }

            // Publish assets
            $this->publishes([
                __DIR__ . '/../resources/css' => public_path('vendor/flowforge/css'),
                __DIR__ . '/../resources/js' => public_path('vendor/flowforge/js'),
            ], 'flowforge-assets');
        }

        // Include the CSS and JS files in Filament 
        // This will ensure they're always loaded in the Filament panel
        $this->callAfterResolving('filament', function () {
            // Added this for debugging
            \Log::debug('Flowforge assets registered: ' . __DIR__ . '/../resources/css/flowforge.css');
        });

        // Register Livewire Components
        Livewire::component('relaticle.flowforge.livewire.kanban-board', KanbanBoard::class);
        
        // Register Blade Components
        $this->registerBladeComponents();

        // Testing
        // Testable::mixin(new TestsFlowforge);
    }

    /**
     * Register Blade components for the Kanban board
     * 
     * @return void
     */
    protected function registerBladeComponents(): void
    {
        // Register components using Blade directive
        Blade::component(\Relaticle\Flowforge\View\Components\Card::class, 'flowforge.card');
        Blade::component(\Relaticle\Flowforge\View\Components\Column::class, 'flowforge.column');
    }

    protected function getAssetPackageName(): ?string
    {
        return 'relaticle/flowforge';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('flowforge', __DIR__ . '/../resources/dist/components/flowforge.js'),
            Css::make('flowforge-styles', __DIR__ . '/../resources/css/flowforge.css'),
            Js::make('flowforge-scripts', __DIR__ . '/../resources/js/flowforge.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FlowforgeCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [
            'flowforge' => [
                'baseUrl' => url('/'),
            ],
        ];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_flowforge_table',
        ];
    }
}
