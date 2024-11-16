<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class EnsureRoleProfiles extends Command
{
    protected $signature = 'users:ensure-role-profiles';

    protected $description = 'Ensure all users have role profiles for their core roles';

    public function handle(): void
    {
        $this->info('Starting role profile creation...');

        User::query()
            ->with(['roles', 'roleProfiles'])
            ->chunkById(100, function (Builder $users) {
                foreach ($users as $user) {
                    $this->processUser($user);
                }
            });
    }

    private function processUser(User $user): void
    {
        $hasActiveProfile = $user->roleProfiles()->where('is_active', true)->exists();

        foreach ($user->roles as $role) {
            if (! in_array($role->name, ['super_admin', 'venue', 'partner', 'concierge'])) {
                continue;
            }

            // Check if user already has a profile for this role
            if ($user->roleProfiles()->where('role_id', $role->id)->exists()) {
                $this->info("User {$user->email} already has profile for {$role->name}");

                continue;
            }

            // Create new profile - make it active if user has no active profile yet
            $user->roleProfiles()->create([
                'role_id' => $role->id,
                'name' => "Default {$role->name} Profile",
                'is_active' => ! $hasActiveProfile, // First profile created will be active
            ]);

            if (! $hasActiveProfile) {
                $hasActiveProfile = true;
            }

            $this->info("Created role profile for {$user->email} with role {$role->name}");
        }
    }
}
