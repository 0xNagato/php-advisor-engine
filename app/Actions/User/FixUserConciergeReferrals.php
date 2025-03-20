<?php

namespace App\Actions\User;

use App\Models\Referral;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

class FixUserConciergeReferrals
{
    use AsAction;

    public function handle(): int
    {
        $referrals = Referral::query()->where('referrer_type', 'concierge')
            ->whereHas('user', function (Builder $query) {
                $query->whereNull('concierge_referral_id');
            })
            ->with(['user:id,concierge_referral_id', 'referrer.concierge'])
            ->get();

        $affectedRecords = 0;

        $referrals->each(function ($referral) use (&$affectedRecords) {
            $user = $referral->user;
            $oldPartnerReferralId = $user->partner_referral_id;
            $newConciergeReferralId = $referral->referrer->concierge->id;

            $user->update([
                'partner_referral_id' => null,
                'concierge_referral_id' => $newConciergeReferralId,
            ]);

            activity()
                ->performedOn($user)
                ->withProperties([
                    'old_partner_referral_id' => $oldPartnerReferralId,
                    'new_concierge_referral_id' => $newConciergeReferralId,
                    'user_id' => $user->id,
                ])
                ->log('User concierge referral updated');

            $affectedRecords++;
        });

        return $affectedRecords;
    }
}
