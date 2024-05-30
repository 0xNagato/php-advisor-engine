<?php

namespace App\Listeners;

use App\Events\AnnouncementCreated;
use App\Models\Message;
use App\Models\User;
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
        $region = $event->announcement->region;

        $recipients = collect($recipient_roles)
            ->flatMap(fn ($role) => User::when($region !== null, function ($query) use ($region) {
                $query->where('region', $region);
            })
                ->role((int) $role)->pluck('id')->all())
            ->concat($recipient_user_ids)
            ->map('intval')
            ->unique();

        $recipients->each(function (int $recipient_id) use ($event) {
            Message::create([
                'user_id' => $recipient_id,
                'announcement_id' => $event->announcement->id,
            ]);
        });
    }
}
