<?php

namespace Database\Factories;

use App\Models\Venue;
use App\Models\VenueCollection;
use App\Models\VenueCollectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueCollectionItem>
 */
class VenueCollectionItemFactory extends Factory
{
    protected $model = VenueCollectionItem::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'note' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the item belongs to a specific venue collection.
     */
    public function forVenueCollection(VenueCollection $venueCollection): static
    {
        return $this->state(fn (array $attributes) => [
            'venue_collection_id' => $venueCollection->id,
        ]);
    }

    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Add a specific note to the item.
     */
    public function withNote(string $note): static
    {
        return $this->state(fn (array $attributes) => [
            'note' => $note,
        ]);
    }
}
