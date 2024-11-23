<?php

namespace App\Actions\User;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckUserHasBookings
{
    use AsAction;

    public function handle(User $user): bool
    {
        // Check for bookings as a concierge
        if ($user->hasRole('concierge')) {
            $hasBookings = Booking::query()
                ->where('concierge_id', $user->concierge->id)
                ->exists();

            if ($hasBookings) {
                return true;
            }
        }

        // Check for bookings as a venue
        if ($user->hasRole('venue')) {
            $hasBookings = Booking::query()
                ->whereHas('venue', function (Builder $query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();

            if ($hasBookings) {
                return true;
            }
        }

        // Check for bookings as a partner
        if ($user->hasRole('partner')) {
            $hasBookings = Booking::query()
                ->where(function ($query) use ($user) {
                    $query->where('partner_concierge_id', $user->partner->id)
                        ->orWhere('partner_venue_id', $user->partner->id);
                })
                ->exists();

            if ($hasBookings) {
                return true;
            }
        }

        return false;
    }
}
