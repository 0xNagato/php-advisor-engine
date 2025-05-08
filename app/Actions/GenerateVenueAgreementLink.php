<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VenueOnboarding;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateVenueAgreementLink
{
    use AsAction;

    public function handle(VenueOnboarding $onboarding): string
    {
        // Encrypt the onboarding ID for security
        $encryptedId = Crypt::encrypt($onboarding->id);
        
        // Generate a signed URL that expires in 30 days
        $signedUrl = URL::temporarySignedRoute(
            'venue.agreement',
            now()->addDays(30),
            ['onboarding' => $encryptedId]
        );
        
        // Convert to a short URL for easier sharing
        try {
            $shortUrl = ShortURL::destinationUrl($signedUrl)
                ->trackVisits()
                ->trackIPAddress()
                ->make();
                
            return $shortUrl->default_short_url;
        } catch (\Exception $e) {
            // If there's an error with the short URL service, return the original signed URL
            return $signedUrl;
        }
    }
}