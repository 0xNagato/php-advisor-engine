<?php

use App\Models\Concierge;
use App\Models\QrCode;
use App\Models\User;
use Livewire\Livewire;

it('defaults referrer concierge to current users concierge account', function () {
    // Create a super admin user with a concierge account
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $concierge = Concierge::factory()->create([
        'user_id' => $user->id,
        'hotel_name' => 'Admin Hotel',
    ]);

    // Act as this user
    $this->actingAs($user);

    // Generate QR codes and check that the referrer defaults correctly
    $countBefore = QrCode::count();

    Livewire::test(\App\Filament\Resources\QrCodeResource\Pages\ListQrCodes::class)
        ->assertSuccessful()
        ->callAction('generate_bulk', [
            'count' => 1,
            'destination' => '',
            'prefix' => 'test-default',
            // Don't specify referrer_concierge_id - it should default
        ])
        ->assertNotified();

    // Check that QR code was created with the referrer in meta
    $qrCode = QrCode::latest()->first();
    expect(QrCode::count())->toBe($countBefore + 1);
    expect($qrCode->meta)->toHaveKey('referrer_concierge_id');
    expect($qrCode->meta['referrer_concierge_id'])->toBe($concierge->id);
});

it('does not default referrer when user has no concierge account', function () {
    // Create a super admin user without a concierge account
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    // Act as this user
    $this->actingAs($user);

    // Generate QR codes
    $countBefore = QrCode::count();

    Livewire::test(\App\Filament\Resources\QrCodeResource\Pages\ListQrCodes::class)
        ->assertSuccessful()
        ->callAction('generate_bulk', [
            'count' => 1,
            'destination' => '',
            'prefix' => 'test-no-default',
        ])
        ->assertNotified();

    // Check that QR code was created without referrer
    $qrCode = QrCode::latest()->first();
    expect(QrCode::count())->toBe($countBefore + 1);

    // When no referrer is set, meta should be null or an empty array
    if ($qrCode->meta !== null) {
        expect($qrCode->meta)->toBeArray();
        expect($qrCode->meta)->not->toHaveKey('referrer_concierge_id');
    } else {
        expect($qrCode->meta)->toBeNull();
    }
});

it('generates QR codes with the defaulted referrer', function () {
    // Create a super admin user with a concierge account
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $concierge = Concierge::factory()->create([
        'user_id' => $user->id,
        'hotel_name' => 'Admin Hotel',
    ]);

    // Act as this user
    $this->actingAs($user);

    // Generate QR codes
    Livewire::test(\App\Filament\Resources\QrCodeResource\Pages\ListQrCodes::class)
        ->callAction('generate_bulk', [
            'count' => 2,
            'destination' => '', // Unassigned
            'prefix' => 'test',
            // referrer_concierge_id should default to the user's concierge
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    // Check that QR codes were created with the referrer in meta
    $qrCodes = \App\Models\QrCode::latest()->take(2)->get();

    foreach ($qrCodes as $qrCode) {
        expect($qrCode->meta)->toHaveKey('referrer_concierge_id');
        expect($qrCode->meta['referrer_concierge_id'])->toBe($concierge->id);
    }
});
