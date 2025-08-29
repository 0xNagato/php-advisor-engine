<?php

use App\Enums\VenueStatus;
use App\Models\Concierge;
use App\Models\Region;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueCollection;
use App\Models\VenueCollectionItem;
use App\Models\VipCode;
use App\Models\VipSession;

test('availability calendar returns venue collection data when VIP session has collection', function () {
    // Create test data
    $region = Region::find('miami'); // Use existing region
    $user = User::factory()->create(['region' => $region->id]);
    $concierge = Concierge::factory()->create();
    $concierge->user()->associate($user)->save();

    $vipCode = VipCode::factory()->create(['concierge_id' => $concierge->id]);

    // Create venues
    $venue1 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);
    $venue2 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);
    $venue3 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);

    // Create venue collection for VIP code
    $collection = VenueCollection::factory()->forVipCode($vipCode)->create([
        'name' => 'Test Collection',
        'description' => 'Test Description',
        'is_active' => true,
    ]);

    // Add venues to collection with notes
    VenueCollectionItem::factory()->create([
        'venue_collection_id' => $collection->id,
        'venue_id' => $venue1->id,
        'note' => 'Amazing food!',
        'is_active' => true,
    ]);

    VenueCollectionItem::factory()->create([
        'venue_collection_id' => $collection->id,
        'venue_id' => $venue2->id,
        'note' => 'Great atmosphere',
        'is_active' => true,
    ]);

    // Create VIP session
    $vipSession = VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'expires_at' => now()->addHours(24),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$vipSession->token,
    ])->getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00:00',
        'region' => $region->id,
    ]));

    $response->assertSuccessful();

    $data = $response->json('data');

    // Check that venue collection data is returned
    expect($data)->toHaveKey('venue_collection');
    expect($data['venue_collection'])->toMatchArray([
        'id' => $collection->id,
        'name' => 'Test Collection',
        'description' => 'Test Description',
        'is_active' => true,
        'source' => 'vip_code',
        'items_count' => 2,
    ]);

    // Check that only venues in collection are returned
    $venueIds = collect($data['venues'])->pluck('id')->toArray();
    expect($venueIds)->toContain($venue1->id);
    expect($venueIds)->toContain($venue2->id);
    expect($venueIds)->not->toContain($venue3->id);

    // Check that collection notes are attached to venues
    $venue1Data = collect($data['venues'])->firstWhere('id', $venue1->id);
    $venue2Data = collect($data['venues'])->firstWhere('id', $venue2->id);

    expect($venue1Data)->toHaveKey('collection_note');
    expect($venue1Data['collection_note'])->toBe('Amazing food!');

    expect($venue2Data)->toHaveKey('collection_note');
    expect($venue2Data['collection_note'])->toBe('Great atmosphere');
});

test('availability calendar falls back to concierge collection when VIP code has no collection', function () {
    // Create test data
    $region = Region::find('ibiza'); // Use existing region
    $user = User::factory()->create(['region' => $region->id]);
    $concierge = Concierge::factory()->create();
    $concierge->user()->associate($user)->save();

    $vipCode = VipCode::factory()->create(['concierge_id' => $concierge->id]);

    // Create venues
    $venue1 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);
    $venue2 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);

    // Create venue collection for concierge (not VIP code)
    $collection = VenueCollection::factory()->forConcierge($concierge)->create([
        'name' => 'Concierge Collection',
        'description' => 'Concierge Description',
        'is_active' => true,
    ]);

    // Add venue to collection
    VenueCollectionItem::factory()->create([
        'venue_collection_id' => $collection->id,
        'venue_id' => $venue1->id,
        'note' => 'Concierge recommendation',
        'is_active' => true,
    ]);

    // Create VIP session
    $vipSession = VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'expires_at' => now()->addHours(24),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$vipSession->token,
    ])->getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00:00',
        'region' => $region->id,
    ]));

    $response->assertSuccessful();

    $data = $response->json('data');

    // Check that venue collection data is returned with concierge source
    expect($data)->toHaveKey('venue_collection');
    expect($data['venue_collection'])->toMatchArray([
        'id' => $collection->id,
        'name' => 'Concierge Collection',
        'description' => 'Concierge Description',
        'is_active' => true,
        'source' => 'concierge',
        'items_count' => 1,
    ]);

    // Check that only venue in collection is returned
    $venueIds = collect($data['venues'])->pluck('id')->toArray();
    expect($venueIds)->toContain($venue1->id);
    expect($venueIds)->not->toContain($venue2->id);
});

test('availability calendar returns all venues when no collection exists', function () {
    // Create test data
    $region = Region::find('mykonos'); // Use existing region
    $user = User::factory()->create(['region' => $region->id]);
    $concierge = Concierge::factory()->create();
    $concierge->user()->associate($user)->save();

    $vipCode = VipCode::factory()->create(['concierge_id' => $concierge->id]);

    // Create venues
    $venue1 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);
    $venue2 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);

    // Create VIP session
    $vipSession = VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'expires_at' => now()->addHours(24),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$vipSession->token,
    ])->getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00:00',
        'region' => $region->id,
    ]));

    $response->assertSuccessful();

    $data = $response->json('data');

    // Check that venue collection data is null (not set)
    expect($data)->toHaveKey('venue_collection');
    expect($data['venue_collection'])->toBeNull();

    // The venues are being returned with schedules
    expect($data['venues'])->toHaveCount(2);
});

test('availability calendar ignores inactive collection items', function () {
    // Create test data
    $region = Region::find('paris'); // Use existing region
    $user = User::factory()->create(['region' => $region->id]);
    $concierge = Concierge::factory()->create();
    $concierge->user()->associate($user)->save();

    $vipCode = VipCode::factory()->create(['concierge_id' => $concierge->id]);

    // Create venues
    $venue1 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);
    $venue2 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);

    // Create venue collection
    $collection = VenueCollection::factory()->forVipCode($vipCode)->create([
        'is_active' => true,
    ]);

    // Add venues to collection - one active, one inactive
    VenueCollectionItem::factory()->create([
        'venue_collection_id' => $collection->id,
        'venue_id' => $venue1->id,
        'note' => 'Active item',
        'is_active' => true,
    ]);

    VenueCollectionItem::factory()->create([
        'venue_collection_id' => $collection->id,
        'venue_id' => $venue2->id,
        'note' => 'Inactive item',
        'is_active' => false,
    ]);

    // Create VIP session
    $vipSession = VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'expires_at' => now()->addHours(24),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$vipSession->token,
    ])->getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00:00',
        'region' => $region->id,
    ]));

    $response->assertSuccessful();

    $data = $response->json('data');

    // Check that only active venue is returned
    $venueIds = collect($data['venues'])->pluck('id')->toArray();
    expect($venueIds)->toContain($venue1->id);
    expect($venueIds)->not->toContain($venue2->id);

    // Check that collection note is only attached to active venue
    $venue1Data = collect($data['venues'])->firstWhere('id', $venue1->id);
    expect($venue1Data)->toHaveKey('collection_note');
    expect($venue1Data['collection_note'])->toBe('Active item');
});

test('availability calendar works without VIP session (no collection filtering)', function () {
    // Create test data
    $region = Region::find('london'); // Use existing region
    $user = User::factory()->create(['region' => $region->id]);

    // Create venues
    $venue1 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);
    $venue2 = Venue::factory()->create([
        'region' => $region->id,
        'status' => VenueStatus::ACTIVE,
    ]);

    // Create a regular token (not VIP session)
    $token = $user->createToken('test-token');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson('/api/calendar?'.http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
        'reservation_time' => '19:00:00',
        'region' => $region->id,
    ]));

    // Just check if we get a response, even if it's empty
    $response->assertStatus(200);

    $data = $response->json('data');

    // Check that venue collection data is null (not set)
    expect($data)->toHaveKey('venue_collection');
    expect($data['venue_collection'])->toBeNull();

    // The venues are being returned with schedules
    expect($data['venues'])->toHaveCount(2);
});
