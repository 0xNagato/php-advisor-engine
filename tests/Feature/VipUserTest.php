<?php

use App\Models\Concierge;
use App\Models\VipCode;

use function Pest\Laravel\get;

test('cannot access booking route without vip code', function () {
    $response = get('/vip/booking');

    $response->assertRedirect('/');
});

test('can access booking page with valid vip code', function () {
    // Create a valid VIP code
    $concierge = Concierge::factory()->create();
    $vipCode = VipCode::create([
        'code' => 'VALIDCODE',
        'concierge_id' => $concierge->id,
        'is_active' => true,
    ]);

    // Access the route using the link accessor - this should redirect to external service
    $response = $this->get($vipCode->link);

    $response->assertStatus(302);
    $response->assertRedirect('https://ibiza.primaapp.com/vip/VALIDCODE');
});

test('cannot access booking page with invalid vip code', function () {
    $response = $this->get(route('vip.booking', 'INVALIDCODE'));

    $response->assertStatus(302);
});

test('cannot access booking page with inactive vip code', function () {
    // Create an inactive VIP code
    $concierge = Concierge::factory()->create();
    $vipCode = VipCode::create([
        'code' => 'INACTIVECODE',
        'concierge_id' => $concierge->id,
        'is_active' => false,
    ]);

    $response = $this->get($vipCode->link);

    $response->assertStatus(302);
});
