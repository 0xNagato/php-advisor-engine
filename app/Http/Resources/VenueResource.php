<?php

namespace App\Http\Resources;

use App\Models\Cuisine;
use App\Models\Specialty;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get the effective tier considering both database and configuration overrides
        $effectiveTier = $this->getEffectiveTier();

        $venueData = [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'address' => $this->resource->address,
            'description' => $this->resource->description,
            'images' => $this->resource->images ?? [],
            'logo' => $this->resource->logo,
            'cuisines' => $this->formatCuisines($this->resource->cuisines ?? []),
            'specialty' => $this->formatSpecialties($this->resource->specialty ?? []),
            'neighborhood' => $this->resource->neighborhood,
            'region' => $this->resource->region,
            'status' => $this->resource->status->value,
            'formatted_location' => $this->resource->getFormattedLocation(),
            'non_prime_time' => $this->resource->non_prime_time,
            'business_hours' => $this->resource->business_hours,
            'tier' => $effectiveTier,
            'tier_label' => $this->getEffectiveTierLabel($effectiveTier),
            'schedules' => VenueScheduleResource::collection($this->resource->schedules),
            'rating' => $this->resource->metadata?->rating,
            'price_level' => $this->resource->metadata?->priceLevel,
            'price_level_display' => $this->resource->metadata?->getPriceLevelDisplay(),
            'rating_display' => $this->resource->metadata?->getRatingDisplay(),
            'review_count' => $this->resource->metadata?->reviewCount,
        ];

        // Add collection note if this venue is part of a collection
        if ($this->resource->collection_note ?? null) {
            $venueData['collection_note'] = $this->resource->collection_note;
        }

        // Add distance and approx_minutes if they were calculated
        if ($this->resource->hasAttribute('approx_minutes') && $this->resource->approx_minutes !== null) {
            $venueData['approx_minutes'] = $this->resource->approx_minutes;
            $venueData['distance_miles'] = $this->resource->distance_miles;
            $venueData['distance_km'] = $this->resource->distance_km;
        }

        return $venueData;
    }

    /**
     * Get the effective tier considering both database tier and configuration overrides
     */
    private function getEffectiveTier(): ?int
    {
        // Check if venue is in tier 1 configuration (Gold)
        $tier1Venues = ReservationService::getVenuesInTier($this->resource->region, 1);
        if (in_array($this->resource->id, $tier1Venues)) {
            return 1;
        }

        // Check if venue is in tier 2 configuration (Silver)
        $tier2Venues = ReservationService::getVenuesInTier($this->resource->region, 2);
        if (in_array($this->resource->id, $tier2Venues)) {
            return 2;
        }

        // Fall back to database tier, or default tier (null for Standard)
        return $this->resource->tier ?? config('venue-tiers.default_tier');
    }

    /**
     * Get the tier label for the effective tier
     */
    private function getEffectiveTierLabel(?int $tier): string
    {
        return ReservationService::getTierLabel($tier);
    }

    /**
     * Format cuisines as key-value pairs
     */
    private function formatCuisines(array $cuisines): array
    {
        $formatted = [];
        $cuisinesList = Cuisine::getNamesList();

        foreach ($cuisines as $cuisineId) {
            if (isset($cuisinesList[$cuisineId])) {
                $formatted[] = [
                    'id' => $cuisineId,
                    'name' => $cuisinesList[$cuisineId],
                ];
            }
        }

        return $formatted;
    }

    /**
     * Format specialties as key-value pairs
     */
    private function formatSpecialties(array $specialties): array
    {
        $formatted = [];
        $specialtiesList = Specialty::query()->pluck('name', 'id');

        foreach ($specialties as $specialtyId) {
            if (isset($specialtiesList[$specialtyId])) {
                $formatted[] = [
                    'id' => $specialtyId,
                    'name' => $specialtiesList[$specialtyId],
                ];
            }
        }

        return $formatted;
    }
}
