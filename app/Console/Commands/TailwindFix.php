<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TailwindFix extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tailwind:fix {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     */
    protected $description = 'Fix Tailwind CSS class conflicts in Blade templates using intelligent JavaScript tools';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Running Tailwind CSS conflict resolution...');

        // Run tailwind-merge conflict resolver
        $command = $this->option('dry-run')
            ? ['node', 'scripts/fix-tailwind.js', '--dry-run', '--verbose']
            : ['node', 'scripts/fix-tailwind.js'];

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('❌ Failed to run Tailwind conflict resolution:');
            $this->line($process->getErrorOutput());

            return 1;
        }

        // Display the results
        $output = trim($process->getOutput());
        if ($output) {
            $this->line($output);
        }

        $this->newLine();
        $this->info('💡 Available tools:');
        $this->line('• php artisan tailwind:fix --dry-run  (preview changes)');
        $this->line('• npm run fix:tailwind                 (fix conflicts)');
        $this->line('• npm run fix:tailwind-dry             (preview changes)');

        return 0;
    }
}
