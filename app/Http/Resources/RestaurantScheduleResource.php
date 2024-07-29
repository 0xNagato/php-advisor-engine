<?php

namespace App\Http\Resources;

use App\Actions\Region\GetUserRegion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $region = GetUserRegion::run();

        return [
            'id' => $this->id,
            'prime_time' => (bool) $this->prime_time,
            'is_bookable' => $this->is_bookable,
            'fee' => moneyWithoutCents(
                $this->resource->fee($request->guest_count),
                $region->currency
            ),
            'has_low_inventory' => $this->has_low_inventory,
        ];
    }
}
