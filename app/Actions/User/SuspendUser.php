<?php

namespace App\Actions\User;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendUser
{
    use AsAction;

    public function handle(User $user): array
    {
        // If user is suspended, unsuspend them
        if ($user->suspended_at !== null) {
            $user->update(['suspended_at' => null]);

            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'unsuspended',
                    'user_email' => $user->email,
                    'user_name' => $user->first_name.' '.$user->last_name,
                ])
                ->log('User was unsuspended');

            return [
                'success' => true,
                'action' => 'unsuspended',
                'message' => 'User has been unsuspended.',
            ];
        }

        // Suspend user
        $user->update(['suspended_at' => now()]);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => 'suspended',
                'user_email' => $user->email,
                'user_name' => $user->first_name.' '.$user->last_name,
            ])
            ->log('User was suspended');

        return [
            'success' => true,
            'action' => 'suspended',
            'message' => 'User has been suspended.',
        ];
    }
}
