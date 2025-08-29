<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class VenueMetadata extends Data
{
    public function __construct(
        #[Between(0, 5)]
        public ?float $rating = null,
        #[Between(1, 4)]
        public ?int $priceLevel = null,
        #[Min(0)]
        public ?int $reviewCount = null,
        public ?string $googlePlaceId = null,
        public ?string $googleDescription = null,
        public ?array $googleTypes = null,
        public ?string $googlePrimaryType = null,
        public ?array $googleAttributes = null,
        public ?array $googlePhotoUrls = null, // Track which Google photo URLs we've already processed
        public ?string $googleEditorialSummary = null, // Google's manually curated summary
        public ?string $googleGenerativeSummary = null, // Google's AI-generated summary
        #[DateFormat('Y-m-d\TH:i:s.u\Z')]
        public ?string $lastSyncedAt = null,
    ) {}

    public function getPriceLevelDisplay(): ?string
    {
        return $this->priceLevel ? str_repeat('$', $this->priceLevel) : null;
    }

    public function getRatingDisplay(): ?string
    {
        return $this->rating ? number_format($this->rating, 1).'/5' : null;
    }
}
