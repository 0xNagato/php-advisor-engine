<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ManyChatService;

class UserManyChatObserver
{
    public function __construct(private readonly ManyChatService $manyChatService) {}

    public function created(User $user): void
    {
        if ($this->shouldSync($user)) {
            $this->manyChatService->syncUser($user);
        }
    }

    public function updated(User $user): void
    {
        if ($this->shouldSync($user) &&
            ($user->isDirty(['phone', 'first_name', 'last_name', 'email', 'region']) || $user->rolesChanged())
        ) {
            $this->manyChatService->syncUser($user);
        }
    }

    private function shouldSync(User $user): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        return $user->relationLoaded('roles') ? $user->roles->isNotEmpty() : $user->roles()->exists();
    }
}
