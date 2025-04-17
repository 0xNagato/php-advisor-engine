<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sushi\Sushi;

/**
 * @property string $id
 * @property string $name
 * @property float $lat
 * @property float $lon
 * @property string $currency
 * @property string $currency_symbol
 * @property float $tax_rate
 * @property string $tax_rate_term
 * @property string $country
 * @property string $timezone
 * @property-read Collection<int, Neighborhood> $neighborhoods
 * @property-read Collection<int, Venue> $venues
 *
 * @mixin IdeHelperRegion
 */
class Region extends Model
{
    use Sushi;

    public $incrementing = false;

    protected $keyType = 'string';

    protected array $rows = [
        [
            'id' => 'miami',
            'name' => 'Miami',
            'lat' => 25.7617,
            'lon' => -80.1918,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'tax_rate' => 0.08,
            'tax_rate_term' => 'Sales Tax',
            'country' => 'United States',
            'timezone' => 'America/New_York',
        ],
        [
            'id' => 'ibiza',
            'name' => 'Ibiza',
            'lat' => 38.9027,
            'lon' => 1.4215,
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'tax_rate' => 0.10,
            'tax_rate_term' => 'VAT',
            'country' => 'Spain',
            'timezone' => 'Europe/Madrid',
        ],
        [
            'id' => 'formentera',
            'name' => 'Formentera',
            'lat' => 38.7075,
            'lon' => 1.4318,
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'tax_rate' => 0.10,
            'tax_rate_term' => 'VAT',
            'country' => 'Spain',
            'timezone' => 'Europe/Madrid',
        ],
        [
            'id' => 'mykonos',
            'name' => 'Mykonos',
            'lat' => 37.4500,
            'lon' => 25.3500,
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'tax_rate' => 0.24,
            'tax_rate_term' => 'VAT',
            'country' => 'Greece',
            'timezone' => 'Europe/Athens',
        ],
        [
            'id' => 'paris',
            'name' => 'Paris',
            'lat' => 48.8566,
            'lon' => 2.3522,
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'tax_rate' => 0.20,
            'tax_rate_term' => 'VAT',
            'country' => 'France',
            'timezone' => 'Europe/Paris',
        ],
        [
            'id' => 'london',
            'name' => 'London',
            'lat' => 51.5074,
            'lon' => -0.1278,
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'tax_rate' => 0.20,
            'tax_rate_term' => 'VAT',
            'country' => 'United Kingdom',
            'timezone' => 'Europe/London',
        ],
        [
            'id' => 'st_tropez',
            'name' => 'St. Tropez',
            'lat' => 43.2692,
            'lon' => 6.6389,
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'tax_rate' => 0.20,
            'tax_rate_term' => 'VAT',
            'country' => 'France',
            'timezone' => 'Europe/Paris',
        ],
        [
            'id' => 'new_york',
            'name' => 'New York',
            'lat' => 40.7128,
            'lon' => -74.0060,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'tax_rate' => 0.08,
            'tax_rate_term' => 'Sales Tax',
            'country' => 'United States',
            'timezone' => 'America/New_York',
        ],
        [
            'id' => 'los_angeles',
            'name' => 'Los Angeles',
            'lat' => 34.0522,
            'lon' => -118.2437,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'tax_rate' => 0.09,
            'tax_rate_term' => 'Sales Tax',
            'country' => 'United States',
            'timezone' => 'America/Los_Angeles',
        ],
        [
            'id' => 'las_vegas',
            'name' => 'Las Vegas',
            'lat' => 36.1699,
            'lon' => -115.1398,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'tax_rate' => 0.08,
            'tax_rate_term' => 'Sales Tax',
            'country' => 'United States',
            'timezone' => 'America/Los_Angeles',
        ],
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('id', config('app.active_regions'));
    }

    public static function default(): Region
    {
        return self::query()->firstWhere('id', config('app.default_region'));
    }

    /**
     * Get the neighborhoods in this region
     *
     * @return HasMany<Neighborhood, $this>
     */
    public function neighborhoods(): HasMany
    {
        return $this->hasMany(Neighborhood::class, 'region', 'id');
    }

    /**
     * @return HasMany<Venue, $this>
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'region', 'id');
    }

    /**
     * Get the timezone for a specific region
     */
    public static function getTimezoneForRegion(string $regionId): ?string
    {
        $region = self::query()->firstWhere('id', $regionId);

        return $region?->timezone;
    }
}
