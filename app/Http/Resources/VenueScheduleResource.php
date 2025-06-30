<?php

namespace App\Http\Resources;

use App\Models\Region;
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
        // Get the venue's region currency using the static method since Region is a Sushi model
        $venueCurrency = Region::query()->where('id', $this->venue->region)->value('currency') ?? 'USD';

        return [
            'id' => $this->id,
            'schedule_template_id' => $this->schedule_template_id,
            'is_bookable' => (bool) $this->is_bookable,
            'prime_time' => (bool) $this->prime_time,
            'time' => [
                'value' => Carbon::createFromFormat('H:i:s', $this->start_time)->format('g:i A'),
                'raw' => Carbon::createFromFormat('H:i:s', $this->start_time)->format('H:i:s'),
            ],
            'date' => $this->booking_date->format('Y-m-d'),
            'fee' => moneyWithoutCents(
                $this->resource->fee($request->guest_count),
                $venueCurrency
            ),
            'has_low_inventory' => (bool) $this->has_low_inventory,
            'is_available' => (bool) $this->is_available,
            'remaining_tables' => (int) $this->remaining_tables,
        ];
    }
}
