<?php

use App\Livewire\Vip\Login;
use App\Models\VipCode;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

test('vip user can see vip login page', function () {
    get('/vip/login')
        ->assertStatus(200)
        ->assertSee('Vip Login')
        ->assertSee('Code')
        ->assertSee('Submit');
});

test('vip user can login with vip code', function () {
    $code = 'Y6KX8SG8';
    $vipCode = VipCode::factory()->create([
        'code' => $code,
        'is_active' => true,
    ]);

    livewire(Login::class)
        ->fillForm(['code' => $code])
        ->call('validateCode')
        ->assertHasNoFormErrors()
        ->assertRedirect(route('vip.booking'));

    assertAuthenticated('vip_code');
    expect(auth('vip_code')->user()->id)->toBe($vipCode->id);
});

test('invalid vip code cannot login', function () {
    livewire(Login::class)
        ->fillForm(['code' => 'INVALID123'])
        ->call('validateCode')
        ->assertHasErrors(['code' => 'The provided code is incorrect.']);

    assertGuest('vip_code');
});

test('vip code can access booking route', function () {
    $vipCode = VipCode::factory()->create([
        'is_active' => true,
    ]);

    $response = actingAs($vipCode, 'vip_code')
        ->get('/vip/booking');

    $response->assertStatus(200);
});

test('guest cannot access booking route', function () {
    $response = get('/vip/booking');

    $response->assertRedirectToRoute('vip.login');
});

test('inactive vip code cannot login', function () {
    VipCode::factory()->create([
        'code' => 'INACTIVE123',
        'is_active' => false,
    ]);

    livewire(Login::class)
        ->fillForm(['code' => 'INACTIVE123'])
        ->call('validateCode')
        ->assertHasErrors(['code' => 'The provided code is incorrect.']);

    assertGuest('vip_code');
});
