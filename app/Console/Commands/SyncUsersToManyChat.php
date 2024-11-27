<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ManyChatService;
use Illuminate\Console\Command;

class SyncUsersToManyChat extends Command
{
    protected $signature = 'manychat:sync-users {--chunk=100}';

    protected $description = 'Sync all users to ManyChat with appropriate tags';

    public function handle(ManyChatService $manyChatService): int
    {
        $users = User::query()
            ->whereNotNull('phone')
            ->whereNotNull('secured_at')
            ->cursor();

        $bar = $this->output->createProgressBar();

        foreach ($users as $user) {
            $success = $manyChatService->syncUser($user);

            if (! $success) {
                $this->error("Failed to sync user {$user->id}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('ManyChat sync completed');

        return self::SUCCESS;
    }
}
