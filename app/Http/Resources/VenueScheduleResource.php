<?php

namespace App\Http\Resources;

use App\Actions\Region\GetUserRegion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueScheduleResource extends JsonResource
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
            'start_time' => Carbon::createFromFormat('H:i:s', $this->start_time)->format('g:i A'),
            'venue_id' => $this->venue_id,
            'date' => $this->booking_date->format('Y-m-d'),
            'fee' => moneyWithoutCents(
                $this->resource->fee($request->guest_count),
                $region->currency
            ),
            'has_low_inventory' => $this->has_low_inventory,
        ];
    }
}
