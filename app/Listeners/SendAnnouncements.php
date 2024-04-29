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

        $recipients = array_map('intval', $recipient_user_ids);

        foreach ($recipient_roles as $role) {
            $user_ids = User::role((int) $role)->pluck('id')->all();
            $recipients = array_merge($recipients, $user_ids);
        }

        collect($recipients)
            ->unique()
            ->each(function (int $recipient_id) use ($event) {
                Message::create([
                    'user_id' => $recipient_id,
                    'announcement_id' => $event->announcement->id,
                ]);
            });
    }
}
