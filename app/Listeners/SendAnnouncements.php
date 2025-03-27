<?php

namespace App\Listeners;

use App\Events\AnnouncementCreated;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAnnouncements implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AnnouncementCreated $event): void
    {
        $recipient_roles = $event->announcement->recipient_roles ?? [];
        $recipient_user_ids = $event->announcement->recipient_user_ids ?? [];
        $regions = $event->announcement->region;

        $recipients = collect($recipient_roles)
            ->flatMap(fn ($role) => User::query()
                ->when(filled($regions), function (Builder $query) use ($regions) {
                    $query->where(function (Builder $q) use ($regions) {
                        // Match users whose primary region is in the announcement regions
                        $q->whereIn('region', $regions);

                        // OR match users who have any of the announcement regions in their notification_regions
                        foreach ($regions as $region) {
                            $q->orWhereJsonContains('notification_regions', $region);
                        }
                    });
                })
                ->role((int) $role)->pluck('id')->all())
            ->concat($recipient_user_ids)
            ->map('intval')
            ->unique();

        // Sort messages by announcement's published_at to maintain correct ordering
        $now = now();
        $recipients->each(function (int $recipient_id) use ($event, $now) {
            Message::query()->create([
                'user_id' => $recipient_id,
                'announcement_id' => $event->announcement->id,
                'created_at' => $event->announcement->published_at,
                'updated_at' => $now,
            ]);
        });
    }
}
