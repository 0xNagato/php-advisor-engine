<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Concierge;
use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class TestConciergeInbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-concierge-inbox';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that a new concierge gets populated with all relevant announcements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Concierge inbox population...');

        // 1. Get the concierge role ID
        $conciergeRoleId = Role::query()->where('name', 'concierge')->first()?->id;

        if (! $conciergeRoleId) {
            $this->error('Concierge role not found');

            return 1;
        }

        // 2. Count existing announcements for concierges
        $announcements = Announcement::query()->whereJsonContains('recipient_roles', (string) $conciergeRoleId)
            ->where('published_at', '<=', now())
            ->get();

        $announcementCount = $announcements->count();
        $this->info("Found {$announcementCount} existing announcements for concierges");

        // 3. Create a test user with concierge role
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Concierge',
            'email' => 'test_concierge_'.time().'@example.com',
        ]);

        $user->assignRole('concierge');
        $this->info("Created test user: {$user->first_name} {$user->last_name} ({$user->email})");

        // 4. Create a concierge model which should trigger our observer
        $concierge = Concierge::query()->create([
            'user_id' => $user->id,
            'hotel_name' => 'Test Hotel',
            'allowed_venue_ids' => [],
        ]);
        $this->info('Created concierge record for user');

        // 5. Count messages created for this user
        $messageCount = Message::query()->where('user_id', $user->id)->count();
        $this->info("User has {$messageCount} messages in their inbox");

        // 6. Validate
        if ($messageCount === $announcementCount) {
            $this->info('SUCCESS: All announcements were properly copied to the user inbox!');
        } else {
            $this->error("FAILED: Expected {$announcementCount} messages, but found {$messageCount}");

            return 1;
        }

        // 7. Clean up if requested
        if ($this->confirm('Do you want to delete the test user and concierge?', true)) {
            $concierge->delete();
            $user->delete();
            $this->info('Test user and concierge deleted');
        }

        return 0;
    }
}
