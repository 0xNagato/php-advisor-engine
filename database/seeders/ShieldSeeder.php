<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"panel_user","guard_name":"web","permissions":[]},{"name":"super_admin","guard_name":"web","permissions":["view_booking","view_any_booking","create_booking","update_booking","restore_booking","restore_any_booking","replicate_booking","reorder_booking","delete_booking","delete_any_booking","force_delete_booking","force_delete_any_booking","view_concierge","view_any_concierge","create_concierge","update_concierge","restore_concierge","restore_any_concierge","replicate_concierge","reorder_concierge","delete_concierge","delete_any_concierge","force_delete_concierge","force_delete_any_concierge","view_restaurant","view_any_restaurant","create_restaurant","update_restaurant","restore_restaurant","restore_any_restaurant","replicate_restaurant","reorder_restaurant","delete_restaurant","delete_any_restaurant","force_delete_restaurant","force_delete_any_restaurant","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","page_AdminDashboard","page_ConciergeDashboard","page_ManageReservations","page_ManageTimeSlots","page_RestaurantDashboard","page_MyProfilePage","widget_AdminStatsOverview","widget_ConciergeStatsOverview","widget_RecentBookings"]},{"name":"concierge","guard_name":"web","permissions":["page_ConciergeDashboard","page_MyProfilePage"]},{"name":"restaurant","guard_name":"web","permissions":["page_ManageTimeSlots","page_RestaurantDashboard","page_MyProfilePage"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
