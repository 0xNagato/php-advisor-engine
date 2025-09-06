<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;

class QrCodeRedirectController extends Controller
{
    /**
     * Handle redirect for unassigned QR codes
     */
    public function handleUnassigned(Request $request, QrCode $qrCode)
    {
        // Handle inactive QR code with helpful message
        if (! $qrCode->is_active) {
            return response()->view('errors.qr-inactive', [
                'message' => 'This QR code is no longer active.',
                'support_message' => 'Please contact your concierge or hotel for a new code.',
            ], 410);
        }

        // If assigned, redirect to the concierge's VIP page
        if ($qrCode->concierge_id && $qrCode->concierge) {
            $vipCode = $qrCode->concierge->vipCodes()->first();
            if ($vipCode) {
                return redirect()->route('v.booking', $vipCode->code);
            }

            // Assigned but no VIP code (shouldn't happen, but handle gracefully)
            return response()->view('errors.qr-error', [
                'message' => 'This QR code is experiencing a technical issue.',
                'support_message' => 'Please contact support for assistance.',
            ], 500);
        }

        // Check if there's a referrer concierge in meta
        $meta = $qrCode->meta ?? [];
        $referrerConciergeId = $meta['referrer_concierge_id'] ?? null;

        // If not in meta, check if current user is a concierge
        if (! $referrerConciergeId) {
            $user = auth()->user();
            if ($user && $user->hasActiveRole('concierge')) {
                $referrerConciergeId = $user->concierge->id;
            }
        }

        // If we have a referrer concierge, redirect to generic invitation with referrer info
        if ($referrerConciergeId) {
            return redirect()->route('join.generic', [
                'type' => 'concierge',
                'qr' => $qrCode->id,
                'referrer' => $referrerConciergeId,
            ]);
        }

        // Otherwise, redirect to a generic concierge signup form
        return redirect()->route('join.generic', [
            'type' => 'concierge',
            'qr' => $qrCode->id,
        ]);
    }
}
