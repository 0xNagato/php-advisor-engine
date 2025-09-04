<?php

use App\Livewire\Concierge\DirectConciergeInvitation;
use App\Models\Concierge;
use App\Models\QrCode;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('initializes form data properly for generic invitation', function () {
    // Create QR code for testing
    $qrCode = QrCode::factory()->create([
        'concierge_id' => null,
        'assigned_at' => null,
    ]);

    // Test the Livewire component directly
    Livewire::test(DirectConciergeInvitation::class, [
        'type' => 'concierge',
        'id' => null,
    ])
        ->assertSet('qrCodeId', null) // Since we're not passing it via request in this test
        ->assertSet('data.first_name', '')
        ->assertSet('data.last_name', '')
        ->assertSet('data.email', '')
        ->assertSet('data.phone', '')
        ->assertSet('data.hotel_name', '')
        ->assertSet('data.password', '')
        ->assertSet('data.passwordConfirmation', '')
        ->assertSet('data.send_agreement_copy', false)
        ->assertHasNoErrors();
});

it('initializes form data with referrer concierge', function () {
    $referrer = Concierge::factory()->create();

    // Simulate the request parameters
    request()->merge([
        'qr' => '123',
        'referrer' => $referrer->id,
    ]);

    // Test generic route
    Livewire::withQueryParams([
        'qr' => '123',
        'referrer' => $referrer->id,
    ])
        ->test(DirectConciergeInvitation::class, [
            'type' => 'concierge',
            'id' => null,
        ])
        ->assertSet('invitingConcierge.id', $referrer->id)
        ->assertSet('data.phone', '')
        ->assertHasNoErrors();
});

it('renders the form without errors', function () {
    $response = get(route('join.generic', [
        'type' => 'concierge',
        'qr' => '123',
    ]));

    $response->assertOk();
    $response->assertSee('Create Your Account');
    $response->assertDontSee('Livewire property');
});
