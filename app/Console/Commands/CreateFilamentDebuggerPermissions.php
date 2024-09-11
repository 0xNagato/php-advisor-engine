<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CreateFilamentDebuggerPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-filament-debugger-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the permissions for the filament debugger';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        collect(config('filament-debugger.permissions'))
            ->map(fn ($permission) => Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => config('filament.auth.guard'),
            ]));

        $superAdminRole = Role::query()->where('name', 'super_admin')->first();

        if ($superAdminRole) {
            collect(config('filament-debugger.permissions'))
                ->map(fn ($permission) => Permission::query()->where('name', $permission)->first())
                ->each(fn ($permission) => $permission->assignRole($superAdminRole));
        } else {
            $this->error('Super admin role not found. Permissions not assigned.');
        }

        $this->info('Permissions created successfully.');

        return CommandAlias::SUCCESS;
    }
}
