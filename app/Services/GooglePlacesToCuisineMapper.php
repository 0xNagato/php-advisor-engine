<?php

namespace App\Services;

use App\Models\Cuisine;
use App\Models\Specialty;

class GooglePlacesToCuisineMapper
{
    /**
     * Map Google Places types to our cuisine IDs
     */
    private const CUISINE_MAPPING = [
        'american_restaurant' => 'american',
        'chinese_restaurant' => 'chinese',
        'french_restaurant' => 'french',
        'indian_restaurant' => 'indian',
        'italian_restaurant' => 'italian',
        'japanese_restaurant' => 'japanese',
        'korean_restaurant' => 'korean',
        'mediterranean_restaurant' => 'mediterranean',
        'mexican_restaurant' => 'mexican',
        'thai_restaurant' => 'thai',
        'greek_restaurant' => 'greek',
        'turkish_restaurant' => 'turkish',
        'spanish_restaurant' => 'spanish',
        'seafood_restaurant' => 'seafood',
        'steakhouse' => 'steakhouse',
        'middle_eastern_restaurant' => 'middle_eastern',
        'peruvian_restaurant' => 'peruvian',
        'asian_restaurant' => 'asian',
        'pizza_restaurant' => 'italian', // Pizza falls under Italian
        'barbecue_restaurant' => 'american', // BBQ is American cuisine
        'sandwich_shop' => 'american',
        'hamburger_restaurant' => 'american',
        'fast_food_restaurant' => 'american',
        'sushi_restaurant' => 'japanese',
        'ramen_restaurant' => 'japanese',
        'taco_restaurant' => 'mexican',
        'brazilian_restaurant' => 'international',
        'continental_restaurant' => 'international',
        'fusion_restaurant' => 'fusion',
        'vegetarian_restaurant' => 'vegan',
    ];

    /**
     * Map Google Places attributes and types to our specialty IDs
     */
    private const SPECIALTY_MAPPING = [
        // Based on attributes
        'live_music' => 'live_music_dj',
        'outdoor_seating' => 'scenic_view',

        // Based on types
        'fine_dining_restaurant' => 'fine_dining',
        'family_restaurant' => 'family_friendly',
    ];

    /**
     * Convert Google Places types to cuisine IDs
     */
    public function mapToCuisines(?array $googleTypes): array
    {
        if (empty($googleTypes)) {
            return [];
        }

        $cuisineIds = [];

        foreach ($googleTypes as $googleType) {
            if (isset(self::CUISINE_MAPPING[$googleType])) {
                $cuisineIds[] = self::CUISINE_MAPPING[$googleType];
            }
        }

        // Remove duplicates and verify cuisines exist
        $validCuisineIds = [];
        foreach (array_unique($cuisineIds) as $cuisineId) {
            if (Cuisine::findById($cuisineId)) {
                $validCuisineIds[] = $cuisineId;
            }
        }

        return $validCuisineIds;
    }

    /**
     * Convert Google Places data to specialty IDs
     */
    public function mapToSpecialties(?array $googleTypes, ?array $googleAttributes): array
    {
        $specialtyIds = [];

        // Map from types
        if (! empty($googleTypes)) {
            foreach ($googleTypes as $googleType) {
                if (isset(self::SPECIALTY_MAPPING[$googleType])) {
                    $specialtyIds[] = self::SPECIALTY_MAPPING[$googleType];
                }
            }
        }

        // Map from attributes
        if (! empty($googleAttributes)) {
            foreach ($googleAttributes as $attribute => $value) {
                if ($value === true && isset(self::SPECIALTY_MAPPING[$attribute])) {
                    $specialtyIds[] = self::SPECIALTY_MAPPING[$attribute];
                }
            }
        }

        // Remove duplicates and verify specialties exist
        $validSpecialtyIds = [];
        foreach (array_unique($specialtyIds) as $specialtyId) {
            if (Specialty::query()->where('id', $specialtyId)->exists()) {
                $validSpecialtyIds[] = $specialtyId;
            }
        }

        return $validSpecialtyIds;
    }

    /**
     * Get all available Google Places types that can be mapped to cuisines
     */
    public function getAvailableGoogleCuisineTypes(): array
    {
        return array_keys(self::CUISINE_MAPPING);
    }

    /**
     * Get all available Google Places types/attributes that can be mapped to specialties
     */
    public function getAvailableGoogleSpecialtyMappings(): array
    {
        return array_keys(self::SPECIALTY_MAPPING);
    }
}
