<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VenueOnboarding;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateVenueAgreementLink
{
    use AsAction;

    public function handle(VenueOnboarding $onboarding): string
    {
        // Encrypt the onboarding ID for security
        $encryptedId = Crypt::encrypt($onboarding->id);

        // Generate a regular URL (no longer using signatures)
        $agreementUrl = route('venue.agreement', ['onboarding' => $encryptedId]);

        // Convert to a short URL for easier sharing
        try {
            $shortUrl = ShortURL::destinationUrl($agreementUrl)
                ->trackVisits()
                ->trackIPAddress()
                ->make();

            return $shortUrl->default_short_url;
        } catch (Exception) {
            // If there's an error with the short URL service, return the original URL
            return $agreementUrl;
        }
    }
}
