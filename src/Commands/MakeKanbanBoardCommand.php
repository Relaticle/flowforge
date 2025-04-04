<?php

namespace Relaticle\Flowforge\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeKanbanBoardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flowforge:make-board {name? : The name of the board page}
                            {--m|model= : The model class to use}
                            {--p|panel= : The panel name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Filament Kanban board page';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get the name argument
        $name = $this->argument('name');

        // Prompt for name if not provided
        if (empty($name)) {
            $name = $this->ask('What should be the name of the board?');

            if (empty($name)) {
                $this->error('A name for the board is required!');

                return self::FAILURE;
            }
        }

        // Get or prompt for model
        $model = $this->option('model');
        if (empty($model)) {
            $model = $this->ask('What model should this board use?');

            if (empty($model)) {
                $this->error('A model class is required!');

                return self::FAILURE;
            }
        }

        // Get panel with default
        $panel = $this->option('panel');
        if (empty($panel)) {
            $panel = $this->ask('Which Filament panel should this board be added to? (Leave empty for default structure)', '');
        }

        // Convert to proper case
        $className = Str::studly($name) . 'BoardPage';
        $modelClass = Str::studly($model);

        // Determine file path based on panel
        $path = empty($panel)
            ? app_path('Filament/Pages/' . $className . '.php')
            : app_path('Filament/' . Str::studly($panel) . '/Pages/' . $className . '.php');

        // Create directories if they don't exist
        $this->files->ensureDirectoryExists(dirname($path));

        // Check if file already exists
        if ($this->files->exists($path)) {
            if (! $this->confirm("The file {$path} already exists. Do you want to overwrite it?")) {
                $this->error('Command cancelled!');

                return self::FAILURE;
            }
        }

        // Create the board page file
        $content = $this->buildClass($className, $modelClass, $panel);
        $this->files->put($path, $content);

        $this->info("Kanban board page [{$className}] created successfully!");
        $this->info("File created at: {$path}");

        return self::SUCCESS;
    }

    /**
     * Build the class file content using the stub.
     */
    protected function buildClass(string $className, string $modelClass, string $panel): string
    {
        $stub = $this->getStub();

        $namespace = empty($panel)
            ? 'App\\Filament\\Pages'
            : 'App\\Filament\\' . Str::studly($panel) . '\\Pages';
        $modelNamespace = 'App\\Models\\' . $modelClass;

        // Replace the stub placeholders
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $className, $stub);
        $stub = str_replace('{{ model }}', $modelClass, $stub);
        $stub = str_replace('{{ modelNamespace }}', $modelNamespace, $stub);
        $stub = str_replace('{{ navigationLabel }}', $className, $stub);
        $stub = str_replace('{{ title }}', $modelClass . ' Board', $stub);

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        $stubPath = __DIR__ . '/../../stubs/kanban-board-page.stub';

        if (! $this->files->exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");

            throw new \RuntimeException("Stub file not found at: {$stubPath}");
        }

        return $this->files->get($stubPath);
    }
}
