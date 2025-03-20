<?php

namespace App\Actions\User;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class FixUserPartnerReferrals
{
    use AsAction;

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        $referrals = DB::select("
            select r.user_id,
                   partner_referral_id,
                   r.referrer_id,
                   p.id as referrerPartnerId
            from users u
                     inner join referrals r on u.id = r.user_id
                     inner join partners p on r.referrer_id = p.user_id
            where referrer_type = 'partner'
              and partner_referral_id <> p.id
        ");

        $affectedRecords = 0;

        foreach ($referrals as $referral) {
            DB::beginTransaction();
            try {
                $user = User::query()->find($referral->user_id);
                $oldPartnerReferralId = $user->partner_referral_id;
                $newPartnerReferralId = $referral->referrerPartnerId;

                $user->update(['partner_referral_id' => $newPartnerReferralId]);

                activity()
                    ->performedOn($user)
                    ->withProperties([
                        'old_partner_referral_id' => $oldPartnerReferralId,
                        'new_partner_referral_id' => $newPartnerReferralId,
                    ])
                    ->log('User partner referral updated');

                DB::commit();
                $affectedRecords++;
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return $affectedRecords;
    }
}
