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

    expect(VipCodeManager::getNavigationLabel())->toBe('VIP Codes');
});

test('vip code form is displayed correctly', function () {
    $user = User::role('concierge')->first();

    actingAs($user)
        ->get(VipCodeManager::getUrl())
        ->assertSuccessful()
        ->assertSee('VIP Codes')
        ->assertSee('Enter VIP Code');
});

test('vip code can be generated', function () {
    $user = User::role('concierge')->first();

    $livewire = actingAs($user)->livewire(VipCodeManager::class);

    // Set the form data with a test VIP code
    $code = \Illuminate\Support\Str::random(8);
    $livewire->set('data.code', $code);

    // Now call the action
    $livewire->call('saveVipCode');

    // Assert the code was saved in the database
    $this->assertDatabaseHas('vip_codes', [
        'code' => $code,
        'concierge_id' => $user->concierge->id,
    ]);
});

test('vip code can be created with different values', function () {
    $user = User::role('concierge')->first();

    $livewire = actingAs($user)->livewire(VipCodeManager::class);

    $codes = ['TEST123', 'VIP456', 'SPECIAL789'];

    foreach ($codes as $code) {
        $livewire->set('data.code', $code)
            ->call('saveVipCode')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vip_codes', [
            'code' => $code,
            'concierge_id' => $user->concierge->id,
        ]);
    }
});
