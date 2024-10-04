<?php

use App\Filament\Pages\Concierge\VipCodeManager;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('concierge can access vip code page', function () {
    $user = User::role('concierge')->first();

    actingAs($user)
        ->get(VipCodeManager::getUrl())
        ->assertSuccessful();
});

test('navigation label is correct for concierge', function () {
    $user = User::role('concierge')->first();

    actingAs($user);

    expect(VipCodeManager::getNavigationLabel())->toBe('My VIP Codes');
});

test('vip code form is displayed correctly', function () {
    $user = User::role('concierge')->first();

    actingAs($user)
        ->get(VipCodeManager::getUrl())
        ->assertSuccessful()
        ->assertSee('My VIP Codes')
        ->assertSee('Create VIP Code');
});

/*test('vip code can be generated', function () {
    $user = User::role('concierge')->first();

    $livewire = actingAs($user)->livewire(VipCode::class);

    $livewire->call('generateVipCode')
        ->assertSet('code', fn ($code) => ! empty($code))
        ->assertSet('link', fn ($link) => str_starts_with($link, config('app.url')));

    // Check if the VIP code was created in the database
    $this->assertDatabaseHas('vip_codes', [
        'code' => $livewire->get('code'),
        'concierge_id' => $user->concierge->id,
    ]);
});*/
