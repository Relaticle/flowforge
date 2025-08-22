<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->filesystem = app(Filesystem::class);
    $this->testFilePath = app_path('Filament/Pages/TestBoardBoardPage.php'); // Correct filename
    $this->pagesDir = app_path('Filament/Pages');

    // Ensure clean state
    if ($this->filesystem->exists($this->testFilePath)) {
        $this->filesystem->delete($this->testFilePath);
    }
});

afterEach(function () {
    // Clean up test files
    if ($this->filesystem->exists($this->testFilePath)) {
        $this->filesystem->delete($this->testFilePath);
    }
});

test('make board command generates file', function () {
    $this->artisan('flowforge:make-board TestBoard --model=Task')
        ->expectsOutput('Creating minimal Kanban Board...')
        ->assertExitCode(0);

    expect($this->filesystem->exists($this->testFilePath))->toBeTrue();
});

test('generated file has correct class name', function () {
    Artisan::call('flowforge:make-board', [
        'name' => 'TestBoard',
        '--model' => 'Task',
    ]);

    $content = $this->filesystem->get($this->testFilePath);

    expect($content)->toContain('class TestBoardBoardPage extends BoardPage');
});

test('generated file has correct namespace', function () {
    Artisan::call('flowforge:make-board', [
        'name' => 'TestBoard',
        '--model' => 'Task',
    ]);

    $content = $this->filesystem->get($this->testFilePath);

    expect($content)->toContain('namespace App\Filament\Pages;');
});

test('generated file has correct imports', function () {
    Artisan::call('flowforge:make-board', [
        'name' => 'TestBoard',
        '--model' => 'Task',
    ]);

    $content = $this->filesystem->get($this->testFilePath);

    expect($content)
        ->toContain('use App\Models\Task;')
        ->toContain('use Relaticle\Flowforge\Board;')
        ->toContain('use Relaticle\Flowforge\BoardPage;')
        ->toContain('use Relaticle\Flowforge\Column;');
});

test('generated file has correct board configuration', function () {
    Artisan::call('flowforge:make-board', [
        'name' => 'TestBoard',
        '--model' => 'Task',
    ]);

    $content = $this->filesystem->get($this->testFilePath);

    expect($content)
        ->toContain('return Task::query();')
        ->toContain('->recordTitleAttribute(\'title\')')
        ->toContain('->columnIdentifier(\'status\')');
});

test('generated file has default columns', function () {
    Artisan::call('flowforge:make-board', [
        'name' => 'TestBoard',
        '--model' => 'Task',
    ]);

    $content = $this->filesystem->get($this->testFilePath);

    expect($content)
        ->toContain('Column::make(\'todo\')')
        ->toContain('Column::make(\'in_progress\')')
        ->toContain('Column::make(\'completed\')');
});

test('command creates directory if needed', function () {
    // Remove Pages directory
    if ($this->filesystem->exists($this->pagesDir)) {
        $this->filesystem->deleteDirectory($this->pagesDir);
    }

    $this->artisan('flowforge:make-board TestBoard --model=Task')
        ->assertExitCode(0);

    expect($this->filesystem->exists($this->pagesDir))->toBeTrue();
    expect($this->filesystem->exists($this->testFilePath))->toBeTrue();
});

test('command handles existing file overwrite', function () {
    // Create existing file
    $this->filesystem->ensureDirectoryExists(dirname($this->testFilePath));
    $this->filesystem->put($this->testFilePath, '<?php // existing content');

    $this->artisan('flowforge:make-board', [
        'name' => 'TestBoard',
        '--model' => 'Task',
    ])
        ->expectsConfirmation('File exists. Overwrite?', 'yes')
        ->assertExitCode(0);

    $content = $this->filesystem->get($this->testFilePath);
    expect($content)->toContain('class TestBoardBoardPage extends BoardPage');
});

test('command respects user choice not to overwrite', function () {
    // Create existing file
    $this->filesystem->ensureDirectoryExists(dirname($this->testFilePath));
    $this->filesystem->put($this->testFilePath, '<?php // existing content');

    $this->artisan('flowforge:make-board', [
        'name' => 'TestBoard',
        '--model' => 'Task',
    ])
        ->expectsConfirmation('File exists. Overwrite?', 'no')
        ->assertExitCode(1);

    $content = $this->filesystem->get($this->testFilePath);
    expect($content)->toBe('<?php // existing content');
});

test('command validates required inputs', function () {
    $this->artisan('flowforge:make-board')
        ->expectsQuestion('Board name?', '')
        ->expectsOutput('Board name required')
        ->assertExitCode(1);
});

test('command validates model input', function () {
    $this->artisan('flowforge:make-board')
        ->expectsQuestion('Board name?', 'TestBoard')
        ->expectsQuestion('Model?', '')
        ->expectsOutput('Model required')
        ->assertExitCode(1);
});

test('command shows usage instructions', function () {
    $this->artisan('flowforge:make-board', [
        'name' => 'TestBoard',
        '--model' => 'Task',
    ])
        ->expectsOutput('Register in admin panel:')
        ->expectsOutputToContain('App\\Filament\\Pages\\TestBoardBoardPage::class');
});
