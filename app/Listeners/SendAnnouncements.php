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
                ->when(!empty($regions), function (Builder $query) use ($regions) {
                    $query->whereIn('region', $regions);
                })
                ->role((int) $role)->pluck('id')->all())
            ->concat($recipient_user_ids)
            ->map('intval')
            ->unique();

        $recipients->each(function (int $recipient_id) use ($event) {
            Message::query()->create([
                'user_id' => $recipient_id,
                'announcement_id' => $event->announcement->id,
            ]);
        });
    }
}
