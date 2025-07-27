<?php

namespace App\Console\Commands;

use App\Models\VenuePlatform;
use Illuminate\Console\Command;

class UpdateVenuePlatformConfigurations extends Command
{
    protected $signature = 'venue-platforms:update-config {--skip-venue-platforms : Skip updating venue platform configurations entirely}';

    protected $description = 'Update platform configurations for existing venue platform connections';

    public function handle(): int
    {
        $skipVenuePlatforms = $this->option('skip-venue-platforms');

        if ($skipVenuePlatforms) {
            $this->warn('⚠️  SKIP-VENUE-PLATFORMS FLAG ENABLED');
            $this->warn('   Venue platform configurations will NOT be updated');
            $this->warn('   All existing platform configs will be preserved');
            $this->newLine();

            return Command::SUCCESS;
        }

        $venuePlatforms = VenuePlatform::with('venue')->get();

        $this->info("Updating platform configurations for {$venuePlatforms->count()} existing venue platform connections...");
        $this->newLine();

        $updatedCount = 0;

        foreach ($venuePlatforms as $venuePlatform) {
            $configurationUpdated = false;

            if ($venuePlatform->platform_type === 'restoo') {
                $venuePlatform->update([
                    'is_enabled' => true,
                    'configuration' => [
                        'api_key' => env('RESTOO_PRIMA_DEV_API'),
                        'account' => env('RESTOO_PRIMA_DEV_ID'),
                    ],
                ]);
                $configurationUpdated = true;
            }

            if ($venuePlatform->platform_type === 'covermanager') {
                $venuePlatform->update([
                    'is_enabled' => true,
                    'configuration' => [
                        'restaurant_id' => env('COVERMANAGER_PRIMA_DEV_RESTAURANT_ID'),
                    ],
                ]);
                $configurationUpdated = true;
            }

            if ($configurationUpdated) {
                $this->line("✅ Updated {$venuePlatform->platform_type} config for venue: {$venuePlatform->venue->name}");
                $updatedCount++;
            }
        }

        $this->newLine();
        $this->info('Completed! Updated configurations for existing venue platform connections only.');
        $this->info('No new platform connections were created.');
        $this->info("Total updated: {$updatedCount}");

        return Command::SUCCESS;
    }
}
