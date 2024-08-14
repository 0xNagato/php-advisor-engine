<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->booking->id,
            'guest_count' => $this->booking->guest_count,
            'dayDisplay' => $this->additional['dayDisplay'],
            'status' => $this->booking->status,
            'venue' => $this->booking->venue->name,
            'logo' => $this->booking->venue->logo_path,
            'total' => money($this->booking->total_with_tax_in_cents, $this->booking->currency)->format(),
            'subtotal' => money($this->booking->total_fee, $this->booking->currency)->format(),
            'tax_rate_term' => $this->booking->tax > 0 ? $this->additional['region']->tax_rate_term : null,
            'tax_amount' => $this->booking->tax > 0 ? money($this->tax_amount_in_cents, $this->booking->currency) : null,
            'bookingUrl' => $this->bookingUrl,
            'qrCode' => $this->qrCode,
        ];
    }
}
