<?php

use App\Models\RoleProfile;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    // Get our test concierge user that has multiple roles
    $this->user = User::role('concierge')->first();

    // Create a token for API authentication
    $this->token = $this->user->createToken('test-token')->plainTextToken;
});

test('authenticated user can fetch their role profiles', function () {
    getJson('/api/profiles', [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'profiles' => [
                '*' => [
                    'id',
                    'name',
                    'role',
                    'is_active',
                ],
            ],
        ]);
});

test('user can switch to their own profile', function () {
    // Get any profile that isn't currently active
    $inactiveProfile = $this->user->roleProfiles()->where('is_active', false)->first();

    // If no inactive profile exists, make one inactive
    if (! $inactiveProfile) {
        $activeProfile = $this->user->roleProfiles()->where('is_active', true)->first();
        $otherProfile = $this->user->roleProfiles()
            ->where('id', '!=', $activeProfile->id)
            ->first();

        if ($otherProfile) {
            $inactiveProfile = $otherProfile;
        } else {
            // Create a new profile if a user doesn't have multiple profiles
            $inactiveProfile = RoleProfile::create([
                'user_id' => $this->user->id,
                'role_id' => $this->user->roles->first()->id,
                'name' => 'Test Profile',
                'is_active' => false,
            ]);
        }
    }

    postJson("/api/profiles/{$inactiveProfile->id}/switch", [], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Role switching is currently disabled from the mobile app, please use the web app to switch roles.',
        ]);

    expect($inactiveProfile->fresh()->is_active)->toBeFalse();
});

test('user cannot switch to another users profile', function () {
    $otherUser = User::role('venue')->first();
    $otherProfile = $otherUser->roleProfiles()->first();

    postJson("/api/profiles/{$otherProfile->id}/switch", [], [
        'Authorization' => 'Bearer '.$this->token,
    ])
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Role switching is currently disabled from the mobile app, please use the web app to switch roles.',
        ]);
});

test('unauthenticated user cannot access profiles', function () {
    getJson('/api/profiles')->assertUnauthorized();
});
