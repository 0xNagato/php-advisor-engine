<?php

use App\Filament\Pages\Concierge\VipCodeManager;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('super admin can access vip code page', function () {
    $admin = User::role('super_admin')->first();

    actingAs($admin)
        ->get(VipCodeManager::getUrl())
        ->assertSuccessful();
});
