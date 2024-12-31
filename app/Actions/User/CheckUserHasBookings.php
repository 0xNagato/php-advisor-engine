<?php

namespace App\Actions\User;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckUserHasBookings
{
    use AsAction;

    public function handle(User $user): bool
    {
        // Check for non-cancelled bookings as a concierge
        if ($user->hasRole('concierge') && $user->concierge) {
            $hasBookings = Booking::query()
                ->where('concierge_id', $user->concierge->id)
                ->where('status', '!=', BookingStatus::CANCELLED)
                ->exists();

            if ($hasBookings) {
                return true;
            }
        }

        // Check for non-cancelled bookings as a venue
        if ($user->hasRole('venue') && $user->venue) {
            $hasBookings = Booking::query()
                ->whereHas('venue', function (Builder $query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('status', '!=', BookingStatus::CANCELLED)
                ->exists();

            if ($hasBookings) {
                return true;
            }
        }

        // Check for non-cancelled bookings as a partner
        if ($user->hasRole('partner') && $user->partner) {
            $hasBookings = Booking::query()
                ->where(function ($query) use ($user) {
                    $query->where('partner_concierge_id', $user->partner->id)
                        ->orWhere('partner_venue_id', $user->partner->id);
                })
                ->where('status', '!=', BookingStatus::CANCELLED)
                ->exists();

            if ($hasBookings) {
                return true;
            }
        }

        return false;
    }
}
