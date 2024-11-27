<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ManyChatService;
use Exception;
use Illuminate\Console\Command;

class TestManyChatSync extends Command
{
    protected $signature = 'manychat:test-sync';

    protected $description = 'Test ManyChat sync with one user from each role (concierge, partner, venue)';

    public function handle(ManyChatService $manyChatService): int
    {
        $this->info('Starting ManyChat test sync...');

        $testUsers = [];

        // Get one user from each role with phone numbers
        foreach (['concierge', 'partner', 'venue'] as $role) {
            $user = User::query()
                ->role($role)
                ->whereNotNull('phone')
                ->whereNotNull('secured_at')
                ->first();

            if (! $user) {
                $this->warn("No {$role} user found with a phone number");

                continue;
            }

            $this->info("Found {$role} user: {$user->name}");
            $this->info("Phone: {$user->phone}");
            $this->info('Region: '.($user->region ?? 'miami'));

            $testUsers[$role] = $user;
        }

        if (blank($testUsers)) {
            $this->error('No test users found with phone numbers');

            return self::FAILURE;
        }

        $this->info('Found test users:');
        foreach ($testUsers as $role => $user) {
            $this->line("- {$role}: {$user->name} ({$user->phone})");
        }

        if ($this->confirm('Do you want to proceed with the sync?')) {
            foreach ($testUsers as $role => $user) {
                $this->info("Syncing {$role} user: {$user->name}");

                try {
                    $success = $manyChatService->syncUser($user);

                    if ($success) {
                        $this->info("✓ Successfully synced {$role} user");
                    } else {
                        $this->error("✗ Failed to sync {$role} user");
                        $this->info('Check the logs for more details');
                    }
                } catch (Exception $e) {
                    $this->error("✗ Exception while syncing {$role} user: ".$e->getMessage());
                }
            }
        }

        return self::SUCCESS;
    }
}
