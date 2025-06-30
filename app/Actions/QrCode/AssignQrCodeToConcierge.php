<?php

namespace App\Actions\QrCode;

use App\Actions\Concierge\EnsureVipCodeExists;
use App\Models\Concierge;
use App\Models\QrCode;
use App\Models\VipCode;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class AssignQrCodeToConcierge
{
    use AsAction;

    /**
     * Assign a QR code to a concierge
     *
     * @param  QrCode  $qrCode  The QR code to assign
     * @param  Concierge  $concierge  The concierge to assign to
     * @param  string|null  $newDestination  Optional new destination URL
     * @return QrCode The updated QR code
     */
    public function handle(QrCode $qrCode, Concierge $concierge, ?string $newDestination = null): QrCode
    {
        return DB::transaction(function () use ($qrCode, $concierge, $newDestination) {
            // If a specific new destination is not provided, use the VIP calendar
            if ($newDestination === null) {
                // Run the action to ensure VIP code exists
                app(EnsureVipCodeExists::class)->handle($concierge);

                // Get the concierge's VIP code
                $vipCode = VipCode::query()
                    ->where('concierge_id', $concierge->id)
                    ->first();

                // Use the VIP code link, which properly routes to v.booking with the code parameter
                $newDestination = route('v.booking', $vipCode->code);
            }

            // Update the short URL destination if it exists
            if ($qrCode->short_url_id) {
                $shortUrl = \AshAllenDesign\ShortURL\Models\ShortURL::query()->find($qrCode->short_url_id);

                if ($shortUrl) {
                    // Update the existing short URL's destination
                    $shortUrl->destination_url = $newDestination;
                    $shortUrl->save();
                } else {
                    // Create a new short URL if the previous one doesn't exist anymore
                    $shortUrl = ShortURL::destinationUrl($newDestination)
                        ->urlKey($qrCode->url_key)
                        ->trackVisits()
                        ->trackIPAddress()
                        ->make();

                    $qrCode->short_url_id = $shortUrl->id;
                }
            } else {
                // Create a new short URL if one doesn't exist
                $shortUrl = ShortURL::destinationUrl($newDestination)
                    ->urlKey($qrCode->url_key)
                    ->trackVisits()
                    ->trackIPAddress()
                    ->make();

                $qrCode->short_url_id = $shortUrl->id;
            }

            // Update the QR code with concierge assignment information
            $qrCode->concierge_id = $concierge->id;
            $qrCode->assigned_at = now();
            $qrCode->save();

            return $qrCode;
        });
    }
}
