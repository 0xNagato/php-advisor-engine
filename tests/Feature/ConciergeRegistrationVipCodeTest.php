<?php

use App\Actions\Concierge\EnsureVipCodeExists;
use App\Models\Concierge;
use App\Models\Referral;
use App\Models\User;
use App\Models\VipCode;

use function Pest\Laravel\assertDatabaseHas;

it('creates a default VIP code when concierge registers', function () {
    // Create user using factory
    $user = User::factory()->create([
        'email' => 'johndoe@example.com',
        'phone' => '+1234567890',
    ]);
    $user->assignRole('concierge');

    // Create concierge
    $concierge = $user->concierge()->create([
        'hotel_name' => 'Test Hotel',
    ]);

    // This is what happens in HandlesConciergeInvitation trait after our change
    app(EnsureVipCodeExists::class)->handle($concierge);

    // Verify a VIP code was created
    assertDatabaseHas('vip_codes', [
        'concierge_id' => $concierge->id,
    ]);

    $vipCode = VipCode::where('concierge_id', $concierge->id)->first();
    expect($vipCode)->not->toBeNull();
    expect($vipCode->code)->toHaveLength(6);
    expect($vipCode->code)->toMatch('/^[A-Z0-9]{6}$/');
});

it('creates a VIP code when registering via QR code and assigns the QR code', function () {
    // Create an unassigned QR code
    $qrCode = \App\Models\QrCode::factory()->create([
        'concierge_id' => null,
        'assigned_at' => null,
    ]);

    // Update the QR code destination to point to unassigned route
    app(\App\Actions\QrCode\UpdateUnassignedQrCodeDestination::class)->handle($qrCode);

    // Simulate the registration process that happens in HandlesConciergeInvitation
    $user = User::factory()->create([
        'email' => 'qruser@example.com',
        'phone' => '+1987654321',
    ]);
    $user->assignRole('concierge');

    $concierge = $user->concierge()->create([
        'hotel_name' => 'QR Hotel',
    ]);

    // Create a referral with QR code ID (as happens in the trait)
    $referral = Referral::factory()->create([
        'type' => 'concierge',
        'referrer_type' => 'self',
        'referrer_id' => $user->id,
        'user_id' => $user->id,
        'email' => 'qruser@example.com',
        'phone' => '+1987654321',
        'qr_code_id' => $qrCode->id,
        'secured_at' => now(),
    ]);

    // This is what happens in HandlesConciergeInvitation trait
    app(EnsureVipCodeExists::class)->handle($concierge);

    // Handle QR code assignment (as happens in the trait)
    if ($referral->qr_code_id) {
        $qrCodeToAssign = \App\Models\QrCode::find($referral->qr_code_id);
        if ($qrCodeToAssign && ! $qrCodeToAssign->concierge_id) {
            app(\App\Actions\QrCode\AssignQrCodeToConcierge::class)->handle($qrCodeToAssign, $concierge);
        }
    }

    // Verify a VIP code was created
    $vipCode = VipCode::where('concierge_id', $concierge->id)->first();
    expect($vipCode)->not->toBeNull();
    expect($vipCode->code)->toHaveLength(6);

    // Verify the QR code was assigned and redirects to VIP booking
    $qrCode->refresh();
    expect($qrCode->concierge_id)->toBe($concierge->id);
    expect($qrCode->assigned_at)->not->toBeNull();

    $shortUrl = \AshAllenDesign\ShortURL\Models\ShortURL::find($qrCode->short_url_id);
    expect($shortUrl->destination_url)->toBe(route('v.booking', $vipCode->code));
});

it('does not create duplicate VIP codes if one already exists', function () {
    $concierge = Concierge::factory()->create();

    // Create a VIP code manually
    $existingCode = VipCode::factory()->create([
        'code' => 'CUSTOM1',
        'concierge_id' => $concierge->id,
    ]);

    // Count VIP codes before
    $countBefore = VipCode::where('concierge_id', $concierge->id)->count();
    expect($countBefore)->toBe(1);

    // Call EnsureVipCodeExists
    app(\App\Actions\Concierge\EnsureVipCodeExists::class)->handle($concierge);

    // Count VIP codes after - should still be 1
    $countAfter = VipCode::where('concierge_id', $concierge->id)->count();
    expect($countAfter)->toBe(1);

    // Verify it's still the same code
    $vipCode = VipCode::where('concierge_id', $concierge->id)->first();
    expect($vipCode->code)->toBe('CUSTOM1');
});
