<?php

use App\Models\User;

use function Pest\Laravel\getJson;

beforeEach(function () {
    // Create a test user with a super_admin role
    $this->superAdminUser = User::factory()->create([
        'email' => 'superadmin@example.com',
    ]);
    $this->superAdminUser->assignRole('super_admin');

    // Create a standard user without a super_admin role
    $this->regularUser = User::factory()->create([
        'email' => 'regularuser@example.com',
    ]);

    // Create an authentication token for the super admin user
    $this->superAdminToken = $this->superAdminUser->createToken('super-admin-token')->plainTextToken;

    // Create an authentication token for the regular user
    $this->regularUserToken = $this->regularUser->createToken('regular-user-token')->plainTextToken;
});

test('unauthenticated user cannot access users endpoint', function () {
    getJson('/api/users')
        ->assertUnauthorized();
});

test('non-super-admin user cannot access users endpoint', function () {
    getJson('/api/users', [
        'Authorization' => 'Bearer '.$this->regularUserToken,
    ])
        ->assertForbidden()
        ->assertJson([
            'message' => 'Unauthorized action.',
        ]);
});

test('super-admin user can access users endpoint', function () {
    getJson('/api/users', [
        'Authorization' => 'Bearer '.$this->superAdminToken,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'timezone',
                    'region',
                    'concierge',
                    'partner',
                    'roles' => [
                        '*' => [
                            'name',
                        ],
                    ],
                ],
            ],
            'first_page_url',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
});

test('users endpoint returns paginated data', function () {
    getJson('/api/users', [
        'Authorization' => 'Bearer '.$this->superAdminToken,
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'current_page',
            'data',
            'first_page_url',
            'last_page',
            'last_page_url',
            'next_page_url',
            'prev_page_url',
            'to',
            'total',
        ]);
});
