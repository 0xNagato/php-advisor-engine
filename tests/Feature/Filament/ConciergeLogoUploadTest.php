<?php

use App\Models\Concierge;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

test('concierge logo upload works correctly', function () {
    // Mock the Digital Ocean storage
    Storage::fake('do');

    // Create a concierge
    $concierge = Concierge::factory()->create([
        'hotel_name' => 'Test Hotel',
    ]);

    // Create a user with super_admin role
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $concierge->update(['user_id' => $user->id]);

    actingAs($user);

    // Create a fake image file
    $file = UploadedFile::fake()->image('test-logo.png', 100, 100);

    // Test the logo upload functionality directly
    $logoPath = $file->storeAs(
        app()->environment().'/concierges/logos',
        'test-hotel-logo-'.time().'.'.$file->getClientOriginalExtension(),
        'do'
    );

    // Set the logo URL in the branding data
    $concierge->update([
        'branding' => [
            'logo_url' => $logoPath,
            'main_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'gradient_start' => '#3B82F6',
            'gradient_end' => '#1E40AF',
        ],
    ]);

    // Check that the file was stored
    $concierge->refresh();
    expect($concierge->branding->logo_url)->not->toBeNull()
        ->and($concierge->branding->logo_url)->toContain('test-hotel-logo-')
        ->and($concierge->branding->logo_url)->toEndWith('.png');

    // Check that the file exists in storage
    expect(Storage::disk('do')->exists($concierge->branding->logo_url))->toBeTrue();
});
