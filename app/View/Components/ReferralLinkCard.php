<?php

namespace App\View\Components;

use AshAllenDesign\ShortURL\Facades\ShortURL;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Component;
use Illuminate\View\View;

class ReferralLinkCard extends Component
{
    public string $referralUrl;

    public string $qrCode;

    public string $qrCodeDownloadUrl;

    public function __construct(public string $type)
    {
        $user = auth()->user();
        abort_unless($user, 403, 'User not authenticated.');

        $cacheKey = "referral_link_{$user->id}_{$type}";

        $this->referralUrl = Cache::rememberForever($cacheKey, function () use ($user, $type) {
            $id = match ($type) {
                'partner' => $user->partner?->id,
                'concierge' => $user->concierge?->id,
                'venue_manager' => $user->id, // Use the user ID for venue managers
                default => null,
            };

            if (! $id) {
                // Handle cases where the user doesn't have the expected role/model instance
                // This might mean logging an error or returning a default/error URL
                Log::error("Could not determine ID for referral link type '{$type}' for user {$user->id}.");

                return '#error-generating-link'; // Or throw an exception
            }

            $url = URL::signedRoute('concierge.join.direct', [
                'type' => $type,
                'id' => $id,
            ]);

            return ShortURL::destinationUrl($url)->make()->default_short_url;
        });

        // Generate QR code for display (without caching)
        $qrDisplayOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => 5,
        ]);

        $this->qrCode = (new QRCode($qrDisplayOptions))->render($this->referralUrl);

        // Generate downloadable QR code (without caching)
        $qrDownloadOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => 10,
        ]);

        $this->qrCodeDownloadUrl = (new QRCode($qrDownloadOptions))->render($this->referralUrl);
    }

    public function render(): View
    {
        return view('components.referral-link-card');
    }
}
