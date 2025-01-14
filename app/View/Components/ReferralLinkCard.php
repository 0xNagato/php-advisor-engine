<?php

namespace App\View\Components;

use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Component;

class ReferralLinkCard extends Component
{
    public string $referralUrl;

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
    }

    public function render()
    {
        return view('components.referral-link-card');
    }
}
