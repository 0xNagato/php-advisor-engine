<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
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
            'restaurant_name' => $this->restaurant_name,
            'status' => $this->status,
            'logo' => $this->resource->logo,
            'non_prime_time' => $this->non_prime_time,
            'business_hours' => $this->business_hours,
            'schedules' => RestaurantScheduleResource::collection($this->schedules),
        ];
    }
}
