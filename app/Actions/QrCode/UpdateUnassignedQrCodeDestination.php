<?php

namespace App\Actions\QrCode;

use App\Models\Concierge;
use App\Models\QrCode;
use AshAllenDesign\ShortURL\Models\ShortURL;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateUnassignedQrCodeDestination
{
    use AsAction;

    /**
     * Update the destination URL for an unassigned QR code
     */
    public function handle(QrCode $qrCode, ?Concierge $referrerConcierge = null): QrCode
    {
        // Only update if the QR code is not assigned to a concierge
        if (! $qrCode->concierge_id) {
            // Build the destination URL - always use the generic QR redirect route
            // The referrer information is stored in meta, not in the URL
            $destination = route('qr.unassigned', ['qrCode' => $qrCode->id]);

            // Update or create the short URL
            if ($qrCode->short_url_id) {
                $shortUrl = ShortURL::find($qrCode->short_url_id);
                if ($shortUrl) {
                    $shortUrl->destination_url = $destination;
                    $shortUrl->save();
                }
            } else {
                // This shouldn't happen for existing QR codes, but handle it just in case
                $shortUrl = \AshAllenDesign\ShortURL\Facades\ShortURL::destinationUrl($destination)
                    ->urlKey($qrCode->url_key)
                    ->trackVisits()
                    ->trackIPAddress()
                    ->make();

                $qrCode->short_url_id = $shortUrl->id;
                $qrCode->save();
            }
        }

        return $qrCode;
    }
}
