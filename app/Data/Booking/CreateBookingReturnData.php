<?php

namespace App\Data\Booking;

use App\Models\Booking;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class CreateBookingReturnData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public Booking $booking,
        public string $bookingUrl,
        public string $bookingVipUrl,
        public string $qrCode,
    ) {}
}
