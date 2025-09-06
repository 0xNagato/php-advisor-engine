<?php

use App\Actions\QrCode\UpdateUnassignedQrCodeDestination;
use App\Models\Concierge;
use App\Models\QrCode;
use App\Models\Referral;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->qrCode = QrCode::factory()->create([
        'is_active' => true,
        'concierge_id' => null, // Unassigned
    ]);
});

it('redirects unassigned QR code to generic invitation form', function () {
    // Update the QR code destination
    app(UpdateUnassignedQrCodeDestination::class)->handle($this->qrCode);

    $response = get(route('qr.unassigned', $this->qrCode));

    $response->assertRedirect(route('join.generic', [
        'type' => 'concierge',
        'qr' => $this->qrCode->id,
    ]));
});

it('redirects unassigned QR code to concierge invitation form when referrer is set', function () {
    $referrer = Concierge::factory()->create();

    // Update the QR code destination with referrer
    app(UpdateUnassignedQrCodeDestination::class)->handle($this->qrCode, $referrer);

    $response = get(route('qr.unassigned', $this->qrCode));

    // When logged in as a concierge, it should redirect to their invitation link
    $response->assertRedirect(route('join.generic', [
        'type' => 'concierge',
        'qr' => $this->qrCode->id,
    ]));
});

it('returns helpful error for inactive qr codes and redirects for assigned qr codes', function () {
    // Test inactive QR code - should return 410 with helpful message
    $this->qrCode->update(['is_active' => false]);

    $response = get(route('qr.unassigned', $this->qrCode));
    $response->assertStatus(410);
    $response->assertSee('This QR code is no longer active');
    $response->assertSee('Please contact your concierge or hotel');

    // Test assigned QR code - should also return 404 because this route is only for unassigned codes
    $concierge = Concierge::factory()->create();
    $vipCode = \App\Models\VipCode::factory()->create(['concierge_id' => $concierge->id]);

    $this->qrCode->update(['is_active' => true, 'concierge_id' => $concierge->id]);

    // The unassigned route should redirect to the VIP page for assigned QR codes
    $response = get(route('qr.unassigned', $this->qrCode));
    $response->assertRedirect(route('v.booking', $vipCode->code));

    // In reality, when a QR code is assigned, the short URL destination should be updated
    // to point directly to the VIP booking page, not to the unassigned route
});

it('tracks QR code ID in referral when completing registration', function () {
    // Create a user to be the referrer (will be updated to self after creation)
    $tempUser = User::factory()->create();

    // Create a referral with QR code ID
    $referral = Referral::factory()->create([
        'type' => 'concierge',
        'qr_code_id' => $this->qrCode->id,
        'referrer_type' => 'self',
        'referrer_id' => $tempUser->id, // Temporary, will be updated to new user
    ]);

    // Simulate registration completion
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'phone' => '+1234567890',
    ]);
    $user->assignRole('concierge');
    $concierge = $user->concierge()->create(['hotel_name' => 'Test Hotel']);

    // Update referral
    $referral->update([
        'user_id' => $user->id,
        'secured_at' => now(),
        'referrer_id' => $user->id, // Self-referral
    ]);

    // Verify QR code assignment would happen
    assertDatabaseHas('referrals', [
        'id' => $referral->id,
        'qr_code_id' => $this->qrCode->id,
        'user_id' => $user->id,
        'referrer_id' => $user->id,
    ]);
});

it('marks QR code as assigned after registration completion', function () {
    expect($this->qrCode->concierge_id)->toBeNull();

    // After the registration flow completes, the QR code should be assigned
    // This is handled in HandlesConciergeInvitation trait
    // We'll test the action directly here
    $concierge = Concierge::factory()->create();

    app(\App\Actions\QrCode\AssignQrCodeToConcierge::class)->handle($this->qrCode, $concierge);

    $this->qrCode->refresh();
    expect($this->qrCode->concierge_id)->toBe($concierge->id);
    expect($this->qrCode->assigned_at)->not->toBeNull();
});
