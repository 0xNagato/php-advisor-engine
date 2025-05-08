<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicAnnouncementController extends Controller
{
    public function show(int $messageId): View|RedirectResponse
    {
        $message = Message::with('announcement')->findOrFail($messageId);

        // If the user is logged in, redirect them to the platform view
        if (auth()->check()) {
            // Mark as read if the user is the recipient
            if ($message->user_id === auth()->id() && is_null($message->read_at)) {
                $message->update(['read_at' => now()]);
            }

            // Redirect to the internal platform view
            return redirect()->route('filament.admin.resources.messages.view', ['record' => $messageId]);
        }

        // Otherwise show the public view
        return view('announcements.public-view', [
            'message' => $message,
        ]);
    }
}
