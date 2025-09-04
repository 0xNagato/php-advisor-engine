<?php

use App\Models\Concierge;
use App\Models\QrCode;

use function Pest\Laravel\get;

it('redirects to generic invitation with referrer parameter', function () {
    $referrer = Concierge::factory()->create();

    // Create an unassigned QR code with referrer in meta
    $qrCode = QrCode::factory()->create([
        'concierge_id' => null,
        'assigned_at' => null,
        'meta' => ['referrer_concierge_id' => $referrer->id],
    ]);

    // Update the QR code destination
    app(\App\Actions\QrCode\UpdateUnassignedQrCodeDestination::class)->handle($qrCode);

    // Follow the redirect
    $response = get(route('qr.unassigned', $qrCode));
    $response->assertRedirect();

    $redirectUrl = $response->headers->get('Location');

    // Should redirect to generic invitation with both qr and referrer params
    expect($redirectUrl)->toContain('join/generic/concierge');
    expect($redirectUrl)->toContain('qr='.$qrCode->id);
    expect($redirectUrl)->toContain('referrer='.$referrer->id);

    // Follow the redirect - should work for anonymous users
    $response2 = get($redirectUrl);
    $response2->assertOk();
    $response2->assertSee('Create Your Account');
    $response2->assertSee($referrer->user->name);
});

it('allows anonymous users to access invitation form', function () {
    // Create an unassigned QR code without referrer
    $qrCode = QrCode::factory()->create([
        'concierge_id' => null,
        'assigned_at' => null,
    ]);

    // Update the QR code destination
    app(\App\Actions\QrCode\UpdateUnassignedQrCodeDestination::class)->handle($qrCode);

    // Anonymous user scans QR code
    $response = get(route('qr.unassigned', $qrCode));
    $response->assertRedirect();

    $redirectUrl = $response->headers->get('Location');

    // Should redirect to generic invitation
    expect($redirectUrl)->toContain('join/generic/concierge');
    expect($redirectUrl)->toContain('qr='.$qrCode->id);

    // Follow redirect as anonymous user
    $response2 = get($redirectUrl);
    $response2->assertOk();
    $response2->assertSee('Create Your Account');
});
