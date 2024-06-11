<?php

namespace App\Actions\Partner;

use App\Models\Referral;
use App\Notifications\Concierge\NotifyConciergeReferral;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Lorisleiva\Actions\Concerns\AsAction;

class InviteConciergeViaSms
{
    use AsAction;

    /**
     * @throws ShortURLException
     */
    public function handle(array $data): Referral
    {
        $referral = Referral::query()->create([
            'referrer_id' => auth()->id(),
            'phone' => $data['phone'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'type' => 'concierge',
            'referrer_type' => strtolower(auth()->user()->main_role),
        ]);

        $referral->notify(new NotifyConciergeReferral(referral: $referral, channel: 'sms'));

        return $referral;
    }
}
