<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicAnnouncementController extends Controller
{
    public function show(int $messageId): View
    {
        $message = Message::with('announcement')->findOrFail($messageId);
        $showPlatformLink = false;
        
        // If the user is logged in, mark the message as read if appropriate
        // and provide a link to platform view instead of redirecting
        if (auth()->check()) {
            // Mark as read if the user is the recipient
            if ($message->user_id === auth()->id() && is_null($message->read_at)) {
                $message->update(['read_at' => now()]);
            }
            
            // Set flag to show platform link
            $showPlatformLink = true;
        }

        // Show the public view for all users with appropriate links
        return view('announcements.public-view', [
            'message' => $message,
            'showPlatformLink' => $showPlatformLink,
            'platformUrl' => route('filament.admin.resources.messages.view', ['record' => $messageId]),
        ]);
    }
}
