<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteOrSuspendUser
{
    use AsAction;

    public function handle(User $user): array
    {
        return DB::transaction(function () use ($user) {
            if (CheckUserHasBookings::run($user)) {
                // Suspend user
                $user->update(['suspended_at' => now()]);

                activity()
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'action' => 'suspended',
                        'user_email' => $user->email,
                        'user_name' => $user->first_name.' '.$user->last_name,
                        'reason' => 'has_bookings',
                    ])
                    ->log('User was suspended due to existing bookings');

                return [
                    'success' => true,
                    'action' => 'suspended',
                    'message' => 'User has been suspended as they have associated bookings.',
                ];
            }

            // Store user info before deletion for logging
            $userInfo = [
                'email' => $user->email,
                'name' => $user->first_name.' '.$user->last_name,
                'roles' => $user->roles->pluck('name')->toArray(),
            ];

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
