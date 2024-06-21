<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class SourceData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?string $id,
        public ?string $name,
        public ?string $brand,
        public ?string $last4,
        public ?string $object,
        public ?string $wallet,
        public ?string $country,
        public ?string $funding,
        public ?string $customer,
        public ?int $expYear,
        public ?array $metadata,
        public ?string $cvcCheck,
        public ?int $expMonth,
        public ?string $addressZip,
        public ?string $fingerprint,
        public ?string $addressCity,
        public ?string $addressLine1,
        public ?string $addressLine2,
        public ?string $addressState,
        public ?string $dynamicLast4,
        public ?string $addressCountry,
        public ?string $addressZipCheck,
        public ?string $addressLine1Check,
        public ?string $tokenizationMethod
    ) {}
}
