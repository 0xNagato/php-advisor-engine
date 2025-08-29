<?php

namespace App\Traits;

use App\Models\Concierge;
use App\Models\VenueCollection;
use App\Models\VenueCollectionItem;
use Illuminate\Support\Facades\Log;

trait ManagesVenueCollections
{
    protected function saveVenueCollection(array $data, $owner): void
    {
        $ownerType = $owner instanceof Concierge ? 'concierge' : 'vip_code';
        $ownerId = $owner->id;

        Log::info("Saving venue collection for {$ownerType}", [
            'owner_id' => $ownerId,
            'owner_type' => $ownerType,
            'venues_count' => count($data['collection_venues'] ?? []),
        ]);

        // Create or update the venue collection
        $collection = VenueCollection::query()->updateOrCreate([
            $ownerType.'_id' => $ownerId,
        ], [
            'name' => $data['collection_name'] ?? ($owner instanceof Concierge
                ? $owner->user->name.' Collection'
                : $owner->code.' Collection'),
            'description' => $data['collection_description'] ?? null,
            'is_active' => $data['collection_is_active'] ?? false,
            'region' => $data['collection_region_id'] ?? throw new \InvalidArgumentException('Region is required for venue collections'),
        ]);

        Log::info('Venue collection saved', [
            'collection_id' => $collection->id,
            'collection_name' => $collection->name,
        ]);

        $this->saveVenueCollectionItems($data['collection_venues'] ?? [], $collection);
    }

    protected function saveVenueCollectionItems(array $venuesData, VenueCollection $collection): void
    {
        $processedVenueIds = [];
        $existingItemIds = $collection->items->pluck('id')->toArray();

        Log::info('Saving venue collection items', [
            'collection_id' => $collection->id,
            'venues_count' => count($venuesData),
            'existing_items_count' => count($existingItemIds),
        ]);

        foreach ($venuesData as $position => $venueData) {
            if (! isset($venueData['venue_id']) || empty($venueData['venue_id'])) {
                Log::info('Skipping venue with no venue_id', ['venue_data' => $venueData]);

                continue;
            }

            $venueId = $venueData['venue_id'];

            // Prevent duplicates
            if (in_array($venueId, $processedVenueIds)) {
                Log::info('Skipping duplicate venue', ['venue_id' => $venueId]);

                continue;
            }

            $processedVenueIds[] = $venueId;

            // Create or update the venue collection item with position
            $item = VenueCollectionItem::query()->updateOrCreate([
                'venue_collection_id' => $collection->id,
                'venue_id' => $venueId,
            ], [
                'position' => $position,
                'note' => $venueData['note'] ?? null,
                'is_active' => $venueData['is_active'] ?? true,
            ]);

            Log::info('Venue collection item saved', [
                'item_id' => $item->id,
                'venue_id' => $venueId,
                'position' => $item->position,
                'note' => $item->note,
                'is_active' => $item->is_active,
            ]);
        }

        // Remove items that are no longer in the collection
        $currentVenueIds = collect($venuesData)->pluck('venue_id')->filter()->toArray();
        $itemsToDelete = $collection->items()->whereNotIn('venue_id', $currentVenueIds)->get();

        foreach ($itemsToDelete as $item) {
            Log::info('Deleting venue collection item', [
                'item_id' => $item->id,
                'venue_id' => $item->venue_id,
            ]);
            $item->delete();
        }

        Log::info('Venue collection items processing complete', [
            'collection_id' => $collection->id,
            'processed_venues' => count($processedVenueIds),
            'deleted_items' => $itemsToDelete->count(),
        ]);
    }

    protected function loadVenueCollectionData($owner): array
    {
        $ownerType = $owner instanceof Concierge ? 'concierge' : 'vip_code';
        $ownerId = $owner->id;

        $venueCollectionData = [];
        $collectionIsActive = true;

        $collection = $owner->venueCollections()->with(['items.venue' => function ($query) {
            $query->select('id', 'name', 'status');
        }, 'items' => function ($query) {
            $query->orderBy('position');
        }])->first();

        Log::info("Loading venue collection data for {$ownerType}", [
            'owner_id' => $ownerId,
            'collection_found' => $collection ? 'yes' : 'no',
            'collection_id' => $collection?->id,
            'items_count' => $collection?->items?->count() ?? 0,
        ]);

        if ($collection) {
            $collectionIsActive = $collection->is_active;
            $venueCollectionData = $collection->items->map(fn ($item) => [
                'id' => $item->id,
                'venue_id' => $item->venue_id,
                'position' => $item->position,
                'note' => $item->note,
                'is_active' => $item->is_active,
            ])->toArray();

            Log::info('Loaded venue collection data', [
                'collection_is_active' => $collectionIsActive,
                'venue_collection_data' => $venueCollectionData,
                'venue_details' => $collection->items->map(fn ($item) => [
                    'item_id' => $item->id,
                    'venue_id' => $item->venue_id,
                    'position' => $item->position,
                    'venue_name' => $item->venue?->name,
                    'venue_loaded' => $item->venue ? 'yes' : 'no',
                ])->toArray(),
            ]);
        }

        return [
            'collection_is_active' => $collectionIsActive,
            'collection_venues' => $venueCollectionData,
            'collection_region_id' => $collection?->region,
            'collection_name' => $collection?->name,
            'collection_description' => $collection?->description,
        ];
    }
}
