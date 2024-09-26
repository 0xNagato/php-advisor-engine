<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class CardData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?string $brand,
        public ?string $last4,
        #[MapInputName('checks')]
        public ChecksData $checks,
        public string|array|null $wallet,
        public ?string $country,
        public ?string $funding,
        public null $mandate,
        public ?string $network,
        public ?int $expYear,
        public ?int $expMonth,
        public ?string $fingerprint,
        #[MapInputName('overcapture')]
        public OvercaptureData $overcapture,
        public ?string $installments,
        #[MapInputName('multicapture')]
        public MulticaptureData $multicapture,
        #[MapInputName('network_token')]
        public NetworkTokenData $networkToken,
        public ?string $threeDSecure,
        public ?int $amountAuthorized,
        #[MapInputName('extended_authorization')]
        public ExtendedAuthorizationData $extendedAuthorization,
        #[MapInputName('incremental_authorization')]
        public IncrementalAuthorizationData $incrementalAuthorization
    ) {
        if (is_array($this->wallet)) {
            $this->wallet = json_encode($this->wallet);
        }
    }
}
