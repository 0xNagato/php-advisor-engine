<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class GooglePlaceData extends Data
{
    public function __construct(
        #[MapInputName('place_id')]
        public ?string $placeId = null,
        public ?string $name = null,
        #[MapInputName('formatted_address')]
        public ?string $formattedAddress = null,
        public ?float $rating = null,
        #[MapInputName('price_level')]
        public ?int $priceLevel = null,
        #[MapInputName('user_ratings_total')]
        public ?int $userRatingsTotal = null,
        #[MapInputName('editorial_summary')]
        public ?string $editorialSummary = null,
        #[MapInputName('generative_summary')]
        public ?string $generativeSummary = null,
        public ?array $types = null,
        #[MapInputName('primary_type')]
        public ?string $primaryType = null,
        #[MapInputName('serves_cocktails')]
        public ?bool $servesCocktails = null,
        #[MapInputName('serves_wine')]
        public ?bool $servesWine = null,
        #[MapInputName('serves_beer')]
        public ?bool $servesBeer = null,
        #[MapInputName('outdoor_seating')]
        public ?bool $outdoorSeating = null,
        #[MapInputName('live_music')]
        public ?bool $liveMusic = null,
        public ?array $photos = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}
}
