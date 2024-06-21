<?php

namespace App\Data\SpecialRequest;

use App\Enums\SpecialRequestStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

class CreateSpecialRequestData extends Data
{
    public function __construct(
        public int $schedule_template_id,
        public int $restaurant_id,
        public int $concierge_id,
        public string $booking_date,
        public string $booking_time,
        public int $party_size,
        #[Max(15)]
        public int $commission_requested_percentage,
        public int $minimum_spend,
        public ?string $special_request,
        public string $customer_first_name,
        public string $customer_last_name,
        public string $customer_phone,
        public ?string $customer_email,
        public ?SpecialRequestStatus $status = SpecialRequestStatus::PENDING,
    ) {}
}
