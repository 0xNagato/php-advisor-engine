<?php

use App\Actions\QrCode\GenerateQrCodes;
use AshAllenDesign\ShortURL\Models\ShortURL;

use function Pest\Laravel\assertDatabaseCount;

it('generates QR codes that redirect to invitation form when no destination provided', function () {
    $action = app(GenerateQrCodes::class);

    $qrCodes = $action->handle(
        count: 5,
        defaultDestination: '', // No destination = invitation form
        prefix: 'test'
    );

    expect($qrCodes)->toHaveCount(5);

    foreach ($qrCodes as $qrCode) {
        expect($qrCode->is_active)->toBeTrue();
        expect($qrCode->url_key)->toStartWith('test-');
        expect($qrCode->concierge_id)->toBeNull();

        // Check that the short URL points to the unassigned route
        $shortUrl = ShortURL::find($qrCode->short_url_id);
        expect($shortUrl->destination_url)->toBe(route('qr.unassigned', ['qrCode' => $qrCode->id]));
    }

    assertDatabaseCount('qr_codes', 5);
});

it('generates regular QR codes when destination is provided', function () {
    $action = app(GenerateQrCodes::class);
    $customUrl = 'https://example.com/custom';

    $qrCodes = $action->handle(
        count: 3,
        defaultDestination: $customUrl,
        prefix: 'regular'
    );

    expect($qrCodes)->toHaveCount(3);

    foreach ($qrCodes as $qrCode) {
        expect($qrCode->is_active)->toBeTrue();
        expect($qrCode->url_key)->toStartWith('regular-');

        // Check that the short URL points to the custom destination
        $shortUrl = ShortURL::find($qrCode->short_url_id);
        expect($shortUrl->destination_url)->toBe($customUrl);
    }
});

it('stores referrer concierge ID in meta when provided', function () {
    $referrerConcierge = \App\Models\Concierge::factory()->create();
    $action = app(GenerateQrCodes::class);

    $qrCodes = $action->handle(
        count: 1,
        defaultDestination: '', // No destination = invitation form
        prefix: null,
        referrerConciergeId: $referrerConcierge->id
    );

    $qrCode = $qrCodes->first();
    expect($qrCode->meta)->toHaveKey('referrer_concierge_id');
    expect($qrCode->meta['referrer_concierge_id'])->toBe($referrerConcierge->id);
});

it('uses default VIP calendar route when no destination provided and not referrer based', function () {
    $action = app(GenerateQrCodes::class);

    // Mock config to return a default destination
    config(['app.default_qr_destination' => route('v.calendar')]);

    $qrCodes = $action->handle(
        count: 1,
        defaultDestination: route('v.calendar'),
        prefix: null
    );

    $qrCode = $qrCodes->first();
    $shortUrl = ShortURL::find($qrCode->short_url_id);

    // Should use the VIP calendar route
    expect($shortUrl->destination_url)->toBe(route('v.calendar'));
});
