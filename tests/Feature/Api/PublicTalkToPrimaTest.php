<?php

use App\Mail\PublicTalkToPrimaMail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

it('allows submission from whitelisted referer', function (): void {
    Mail::fake();

    Config::set('forms.allowed_origins', ['example.com', '*.primavip.co']);

    $payload = [
        'role' => 'Concierge',
        'name' => 'John Doe',
        'company' => 'Luxury Hotel Group',
        'email' => 'john@example.com',
        'phone' => '+1 555 000 1111',
        'city' => 'New York',
        'preferred_contact_time' => 'Morning',
        'message' => 'I would like to join PRIMA as a concierge',
    ];

    $response = test()->withHeaders([
        'Referer' => 'https://www.primavip.co/contact',
    ])->postJson('/api/public/talk-to-prima', $payload);

    $response->assertSuccessful();

    Mail::assertSent(PublicTalkToPrimaMail::class);
});

it('blocks submission from non-whitelisted referer', function (): void {
    Mail::fake();

    Config::set('forms.allowed_origins', ['example.com']);

    $payload = [
        'role' => 'Restaurant',
        'name' => 'Jane Doe',
        'company' => 'Fine Dining Restaurant',
        'email' => 'jane@example.com',
        'phone' => '+1 555 000 1112',
        'city' => 'Los Angeles',
        'preferred_contact_time' => 'Afternoon',
        'message' => 'I would like to explore a partnership opportunity with PRIMA',
    ];

    $response = test()->withHeaders([
        'Referer' => 'https://not-allowed.test/contact',
    ])->postJson('/api/public/talk-to-prima', $payload);

    $response->assertForbidden();
    $response->assertJson(['message' => 'Forbidden']);

    Mail::assertNothingSent();
});

it('blocks submission without referer header', function (): void {
    Mail::fake();

    Config::set('forms.allowed_origins', ['example.com']);

    $payload = [
        'role' => 'Other',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+1 555 000 1113',
        'message' => 'Test message',
    ];

    $response = test()->postJson('/api/public/talk-to-prima', $payload);

    $response->assertForbidden();
    $response->assertJson(['message' => 'Forbidden']);

    Mail::assertNothingSent();
});

it('validates input', function (): void {
    Config::set('forms.allowed_origins', ['example.com']);

    $response = test()->withHeaders([
        'Referer' => 'https://example.com/contact',
    ])->postJson('/api/public/talk-to-prima', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['role', 'name', 'phone']);
});

it('supports wildcard subdomain matching', function (): void {
    Mail::fake();

    Config::set('forms.allowed_origins', ['*.primavip.co']);

    $payload = [
        'role' => 'Hotel / Property',
        'name' => 'Subdomain Test',
        'company' => 'Test Hotel',
        'email' => 'subdomain@test.com',
        'phone' => '+1 555 000 1114',
        'city' => 'Miami',
        'message' => 'Testing subdomain wildcard',
    ];

    $response = test()->withHeaders([
        'Referer' => 'https://staging.primavip.co/form',
    ])->postJson('/api/public/talk-to-prima', $payload);

    $response->assertSuccessful();

    Mail::assertSent(PublicTalkToPrimaMail::class);
});
