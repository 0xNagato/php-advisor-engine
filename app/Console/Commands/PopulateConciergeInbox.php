<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Concierge;
use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;

class PopulateConciergeInbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-concierge-inbox {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually populate a concierge\'s inbox with all announcements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get User ID
        $userId = $this->argument('user_id');

        if (! $userId) {
            // List concierges
            $this->info('No user_id provided. Listing all concierge users:');
            $conciergeUsers = User::role('concierge')->get(['id', 'first_name', 'last_name', 'email']);

            $this->table(
                ['ID', 'Name', 'Email'],
                $conciergeUsers->map(fn ($user) => [
                    'ID' => $user->id,
                    'Name' => "{$user->first_name} {$user->last_name}",
                    'Email' => $user->email,
                ])
            );

            $userId = $this->ask('Enter the ID of the concierge user to populate inbox for:');
        }

        // Find the user
        $user = User::query()->find($userId);
        if (! $user) {
            $this->error("User with ID {$userId} not found");

            return 1;
        }

        // Check if user is a concierge
        $concierge = Concierge::query()->where('user_id', $user->id)->first();
        if (! $concierge) {
            $this->error("User with ID {$userId} is not a concierge");

            return 1;
        }

        $this->info("Processing inbox for concierge: {$user->first_name} {$user->last_name} (ID: {$user->id})");

        // Get all announcements
        $announcements = Announcement::query()->where('published_at', '<=', now())->get();
        $this->info("Found {$announcements->count()} published announcements");

        // Create Messages
        $created = 0;
        foreach ($announcements as $announcement) {
            // Check if message already exists
            $exists = Message::query()->where([
                'user_id' => $user->id,
                'announcement_id' => $announcement->id,
            ])->exists();

            if (! $exists) {
                Message::query()->create([
                    'user_id' => $user->id,
                    'announcement_id' => $announcement->id,
                ]);
                $created++;
            }
        }

        $this->info("Created {$created} new messages in {$user->first_name}'s inbox");
        $this->info('Total messages in inbox: '.Message::query()->where('user_id', $user->id)->count());

        return 0;
    }
}
