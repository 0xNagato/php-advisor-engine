<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Concierge;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UpdateAllConciergeInboxes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-all-concierge-inboxes {--force} {--only-concierge-announcements} {--batch-size=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all existing concierges\' inboxes with published announcements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = $this->option('force');
        $onlyConciergeAnnouncements = $this->option('only-concierge-announcements');
        $batchSize = (int) $this->option('batch-size');

        if (! $force) {
            if (! $this->confirm('This will add all published announcements to all concierge inboxes. Continue?', true)) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        $start = microtime(true);
        $this->info('Starting inbox update...');

        // Get all concierge users
        $conciergeRole = Role::query()->where('name', 'concierge')->first();
        if (! $conciergeRole) {
            $this->error('Concierge role not found');

            return 1;
        }

        $concierges = Concierge::with('user')
            ->select('id', 'user_id')
            ->get();

        $this->info("Found {$concierges->count()} concierge records");

        // Get all published announcements
        $query = Announcement::query()->select('id')
            ->where('published_at', '<=', now());

        // If only-concierge-announcements flag is set, only include announcements targeted at concierges
        if ($onlyConciergeAnnouncements) {
            $query->where(function ($q) use ($conciergeRole) {
                $q->whereJsonContains('recipient_roles', (string) $conciergeRole->id)
                    ->orWhereJsonContains('recipient_roles', $conciergeRole->id);
            });
        }

        $announcements = $query->get();
        $this->info("Found {$announcements->count()} published announcements");

        if ($announcements->isEmpty()) {
            $this->warn('No announcements found to add to inboxes.');

            return 0;
        }

        // Get valid user IDs from concierges
        $userIds = $concierges->pluck('user_id')->filter()->values();

        $this->info("Processing {$userIds->count()} valid concierge users");

        // Process in chunks to avoid memory issues
        $messagesCreated = 0;
        $userIds->chunk($batchSize)->each(function (Collection $userIdsChunk) use ($announcements, &$messagesCreated) {
            $existingMessages = DB::table('messages')
                ->whereIn('user_id', $userIdsChunk)
                ->whereIn('announcement_id', $announcements->pluck('id'))
                ->select('user_id', 'announcement_id')
                ->get();

            // Create a lookup table for faster existence checking
            $existingLookup = [];
            foreach ($existingMessages as $message) {
                $key = $message->user_id.'_'.$message->announcement_id;
                $existingLookup[$key] = true;
            }

            $messagesToInsert = [];
            $now = now();

            foreach ($userIdsChunk as $userId) {
                foreach ($announcements as $announcement) {
                    $key = $userId.'_'.$announcement->id;
                    if (! isset($existingLookup[$key])) {
                        $messagesToInsert[] = [
                            'user_id' => $userId,
                            'announcement_id' => $announcement->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            // Insert in batches
            foreach (array_chunk($messagesToInsert, 500) as $chunk) {
                if (filled($chunk)) {
                    DB::table('messages')->insert($chunk);
                    $messagesCreated += count($chunk);
                }
            }

            $this->info("Processed batch: {$userIdsChunk->count()} users, created ".count($messagesToInsert).' messages');
        });

        $duration = round(microtime(true) - $start, 2);
        $this->newLine();
        $this->info("Completed in {$duration} seconds. Created {$messagesCreated} messages.");

        return 0;
    }
}
