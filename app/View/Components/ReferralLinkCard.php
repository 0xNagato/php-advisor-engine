<?php

namespace App\View\Components;

use AshAllenDesign\ShortURL\Facades\ShortURL;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Component;

class ReferralLinkCard extends Component
{
    public string $referralUrl;

    public string $qrCode;

    public string $qrCodeDownloadUrl;

    public function __construct(public string $type)
    {
        $user = auth()->user();
        $cacheKey = "referral_link_{$user->id}_{$type}";

        $this->referralUrl = Cache::rememberForever($cacheKey, function () use ($user, $type) {
            $id = $user->{$type}->id;

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

    public function render()
    {
        return view('components.referral-link-card');
    }
}
