<?php

use App\Models\Concierge;
use App\Models\QrCode;
use App\Models\User;
use App\Models\VipCode;
use AshAllenDesign\ShortURL\Models\ShortURL;

use function Pest\Laravel\get;

it('redirects to concierge VIP page after QR code is assigned', function () {
    // Create a concierge with a VIP code
    $user = User::factory()->create();
    $concierge = Concierge::factory()->for($user)->create();
    $vipCode = VipCode::factory()->for($concierge)->create(['code' => 'TESTVIP123']);

    // Create an unassigned QR code
    $qrCode = QrCode::factory()->create([
        'concierge_id' => null,
        'assigned_at' => null,
    ]);

    // Update the QR code destination to point to unassigned route
    app(\App\Actions\QrCode\UpdateUnassignedQrCodeDestination::class)->handle($qrCode);

    // Get the short URL
    $shortUrl = ShortURL::find($qrCode->short_url_id);
    expect($shortUrl->destination_url)->toContain('qr/unassigned');

    // Assign the QR code to the concierge
    app(\App\Actions\QrCode\AssignQrCodeToConcierge::class)->handle($qrCode, $concierge);

    // Refresh the models
    $qrCode->refresh();
    $shortUrl->refresh();

    // The short URL should now point to the VIP booking page
    expect($shortUrl->destination_url)->toContain('v/TESTVIP123');

    // Accessing the QR code unassigned route should redirect to VIP page
    $response = get(route('qr.unassigned', $qrCode));
    $response->assertRedirect(route('v.booking', 'TESTVIP123'));

    // Accessing the short URL should go directly to VIP page
    $response2 = get($shortUrl->default_short_url);
    $response2->assertRedirect(route('v.booking', 'TESTVIP123'));
});

it('returns helpful error when accessing unassigned route for inactive QR code', function () {
    $qrCode = QrCode::factory()->create([
        'is_active' => false,
        'concierge_id' => null,
    ]);

    $response = get(route('qr.unassigned', $qrCode));
    $response->assertStatus(410);
    $response->assertSee('This QR code is no longer active');
});

it('returns custom 404 for invalid QR code', function () {
    $response = get('/qr/unassigned/99999999');
    $response->assertStatus(404);
    $response->assertSee('This QR code was not found');
    $response->assertSee('Please verify the code or contact support');
});
