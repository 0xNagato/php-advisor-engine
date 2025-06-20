<?php

namespace App\Http\Resources;

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

        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'logo' => $this->resource->logo,
            'non_prime_time' => $this->non_prime_time,
            'business_hours' => $this->business_hours,
            'tier' => $effectiveTier,
            'tier_label' => $this->getEffectiveTierLabel($effectiveTier),
            'schedules' => VenueScheduleResource::collection($this->schedules),
        ];
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
}
