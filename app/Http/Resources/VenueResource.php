<?php

namespace App\Http\Resources;

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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'logo' => $this->resource->logo,
            'non_prime_time' => $this->non_prime_time,
            'business_hours' => $this->business_hours,
            'tier' => $this->tier,
            'tier_label' => $this->tier_label,
            'schedules' => VenueScheduleResource::collection($this->schedules),
        ];
    }
}
