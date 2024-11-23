<?php

namespace App\Actions\User;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteOrSuspendUser
{
    use AsAction;

    public function handle(User $user): array
    {
        return DB::transaction(function () use ($user) {
            if (CheckUserHasBookings::run($user)) {
                // Suspend user if they have non-cancelled bookings
                $user->update(['suspended_at' => now()]);

                activity()
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'action' => 'suspended',
                        'user_email' => $user->email,
                        'user_name' => $user->first_name.' '.$user->last_name,
                        'reason' => 'has_active_bookings',
                    ])
                    ->log('User was suspended due to existing active bookings');

                return [
                    'success' => true,
                    'action' => 'suspended',
                    'message' => 'User has been suspended as they have associated active bookings.',
                ];
            }

            // Store user info before deletion for logging
            $userInfo = [
                'email' => $user->email,
                'name' => $user->first_name.' '.$user->last_name,
                'roles' => $user->roles->pluck('name')->toArray(),
            ];

            // Get house partner for reassigning referrals
            $housePartner = Partner::query()
                ->whereHas('user', function (Builder $query) {
                    $query->where('email', 'house.partner@primavip.co');
                })
                ->first();

            // Update referrals where user was a referrer
            if ($user->hasRole(['concierge', 'partner'])) {
                Referral::query()
                    ->where('referrer_id', $user->id)
                    ->update([
                        'referrer_id' => $housePartner->user_id,
                        'referrer_type' => 'partner',
                    ]);

                // Update any users that had this user as their referrer
                User::query()
                    ->where('concierge_referral_id', $user->concierge?->id)
                    ->orWhere('partner_referral_id', $user->partner?->id)
                    ->update([
                        'partner_referral_id' => $housePartner->id,
                        'concierge_referral_id' => null,
                    ]);
            }

            // Delete the referral where user was the referred user
            Referral::query()
                ->where('user_id', $user->id)
                ->delete();

            // Delete cancelled bookings
            if ($user->hasRole('concierge')) {
                Booking::query()->where('concierge_id', $user->concierge->id)
                    ->where('status', BookingStatus::CANCELLED)
                    ->delete();
            }
            if ($user->hasRole('venue')) {
                Booking::query()->whereHas('venue', function (Builder $query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                    ->where('status', BookingStatus::CANCELLED)
                    ->delete();
            }
            if ($user->hasRole('partner')) {
                Booking::query()->where(function ($query) use ($user) {
                    $query->where('partner_concierge_id', $user->partner->id)
                        ->orWhere('partner_venue_id', $user->partner->id);
                })
                    ->where('status', BookingStatus::CANCELLED)
                    ->delete();
            }

            // Delete associated role models
            if ($user->hasRole('concierge')) {
                $user->concierge->delete();
            }
            if ($user->hasRole('partner')) {
                $user->partner->delete();
            }
            if ($user->hasRole('venue')) {
                $user->venue->delete();
            }

            // Delete role profiles
            $user->roleProfiles()->delete();

            // Log before actual user deletion
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'deleted',
                    'user_info' => $userInfo,
                ])
                ->log('User was deleted');

            // Delete the user
            $user->delete();

            return [
                'success' => true,
                'action' => 'deleted',
                'message' => 'User and associated data have been deleted.',
            ];
        });
    }
}
