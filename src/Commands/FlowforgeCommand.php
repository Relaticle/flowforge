<?php

namespace Relaticle\Flowforge\Commands;

use Illuminate\Console\Command;

class FlowforgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flowforge:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and set up Flowforge resources';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🔄 Installing Flowforge package...');
        
        // Publish assets and resources
        $this->info('Publishing assets...');
        $this->call('vendor:publish', [
            '--tag' => 'flowforge-assets',
            '--force' => true,
        ]);
        
        $this->info('✅ Flowforge installed successfully!');
        
        return self::SUCCESS;
    }
}