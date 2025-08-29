<?php

namespace Relaticle\Flowforge\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class MakeKanbanBoardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flowforge:make-board {name? : Board name} {--m|model= : Model class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a minimal Filament Kanban board page';

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
        $this->info('Creating minimal Kanban Board...');

        // Get board name
        $name = $this->argument('name') ?: $this->ask('Board name?', 'Tasks');
        if (empty($name)) {
            $this->error('Board name required');

            return self::FAILURE;
        }

        // Get model
        $model = $this->option('model') ?: $this->ask('Model?', 'Task');
        if (empty($model)) {
            $this->error('Model required');

            return self::FAILURE;
        }

        // Generate class name
        $className = Str::studly($name);
        $modelClass = Str::studly($model);

        // Determine file path
        $path = app_path('Filament/Pages/' . $className . '.php');
        $this->files->ensureDirectoryExists(dirname($path));

        // Check if file exists
        if ($this->files->exists($path)) {
            if (! $this->confirm('File exists. Overwrite?')) {
                return self::FAILURE;
            }
        }

        // Generate file
        $content = $this->buildClass($className, $modelClass);
        $this->files->put($path, $content);

        $this->info("Board created: {$path}");
        $this->newLine();

        $this->info('Register in admin panel:');
        $this->line("->pages([\n    App\\Filament\\Pages\\{$className}::class,\n])");

        $this->newLine();
        $this->info('For create/edit functionality, see documentation at:');
        $this->line('https://github.com/relaticle/flowforge');

        return self::SUCCESS;
    }

    /**
     * Build the class file content using the stub.
     */
    protected function buildClass(string $className, string $modelClass): string
    {
        // Get the base stub
        $stub = $this->getStub('kanban-board-page.stub');

        // Replace placeholders
        $stub = str_replace('{{ namespace }}', 'App\\Filament\\Pages', $stub);
        $stub = str_replace('{{ class }}', $className, $stub);
        $stub = str_replace('{{ model }}', $modelClass, $stub);
        $stub = str_replace('{{ modelNamespace }}', 'App\\Models\\' . $modelClass, $stub);
        $stub = str_replace('{{ navigationLabel }}', Str::title(Str::snake($className, ' ')), $stub);
        $stub = str_replace('{{ title }}', $modelClass . ' Board', $stub);
        $stub = str_replace('{{ titleField }}', 'title', $stub);
        $stub = str_replace('{{ statusField }}', 'status', $stub);

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(string $name): string
    {
        $stubPath = __DIR__ . '/../../stubs/' . $name;

        if (! $this->files->exists($stubPath)) {
            throw new RuntimeException("Stub file not found: {$stubPath}");
        }

        return $this->files->get($stubPath);
    }
}
