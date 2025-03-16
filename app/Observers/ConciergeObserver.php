<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Models\Concierge;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Throwable;

class ConciergeObserver
{
    /**
     * Handle the Concierge "created" event.
     */
    public function created(Concierge $concierge): void
    {
        // Skip completely when running tests to improve test performance
        if ($this->isTestEnvironment()) {
            return;
        }

        try {
            // Get the concierge role ID
            $conciergeRole = Role::query()->where('name', 'concierge')->first();

            if (! $conciergeRole) {
                Log::warning('ConciergeObserver: Concierge role not found when trying to populate inbox');

                return;
            }

            // Get all published announcements targeted at concierges - using the most efficient approach
            $announcements = Announcement::query()
                ->where(function ($query) use ($conciergeRole) {
                    // Check if recipient_roles contains the concierge role ID in various formats
                    $query->whereJsonContains('recipient_roles', (string) $conciergeRole->id)
                        ->orWhereJsonContains('recipient_roles', $conciergeRole->id);
                })
                ->where('published_at', '<=', now())
                ->get();

            // If we found no announcements specifically for concierges, try a broader approach
            if ($announcements->isEmpty()) {
                $announcements = Announcement::query()->whereNull('recipient_roles')
                    ->orWhere('recipient_roles', '[]')
                    ->where('published_at', '<=', now())
                    ->get();
            }

            // Use bulk insert if possible for better performance
            $messagesToInsert = [];
            $now = now();

            foreach ($announcements as $announcement) {
                // Check if a message already exists for this user and announcement
                $exists = Message::query()->where('user_id', $concierge->user_id)
                    ->where('announcement_id', $announcement->id)
                    ->exists();

                // If not, prepare data for bulk insert
                if (! $exists) {
                    $messagesToInsert[] = [
                        'user_id' => $concierge->user_id,
                        'announcement_id' => $announcement->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Bulk insert if we have messages to create
            if (filled($messagesToInsert)) {
                // Break into chunks to avoid too large queries
                foreach (array_chunk($messagesToInsert, 100) as $chunk) {
                    Message::query()->insert($chunk);
                }

                Log::info('ConciergeObserver: Created '.count($messagesToInsert)." inbox messages for concierge user ID: {$concierge->user_id}");
            }
        } catch (Throwable $e) {
            Log::error('ConciergeObserver: Error populating concierge inbox: '.$e->getMessage(), [
                'concierge_id' => $concierge->id,
                'user_id' => $concierge->user_id,
            ]);
        }
    }

    /**
     * Determine if we're running in a test environment.
     */
    private function isTestEnvironment(): bool
    {
        return app()->environment('testing') ||
               (defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING) ||
               env('APP_ENV') === 'testing';
    }
}
