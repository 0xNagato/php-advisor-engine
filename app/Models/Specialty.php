<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Sushi\Sushi;

/**
 * @mixin IdeHelperSpecialty
 */
class Specialty extends Model
{
    use Sushi;

    public $incrementing = false;

    protected $keyType = 'string';

    protected array $rows = [
        [
            'id' => 'waterfront',
            'name' => 'Waterfront',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'sunset_view',
            'name' => 'Sunset view',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'scenic_view',
            'name' => 'Scenic view',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'traditional_ibiza',
            'name' => 'Traditional Ibiza',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'on_the_beach',
            'name' => 'On the Beach',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'family_friendly',
            'name' => 'Family Friendly',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'fine_dining',
            'name' => 'Fine Dining',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'romantic_atmosphere',
            'name' => 'Romantic Atmosphere',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'live_music_dj',
            'name' => 'Live Music/DJ',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'farm_to_table',
            'name' => 'Farm-to-Table',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'vegetarian_vegan_options',
            'name' => 'Vegetarian/Vegan Options',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
        [
            'id' => 'michelin_repsol_recognition',
            'name' => 'Michelin/Repsol Recognition',
            'regions' => 'miami,ibiza,formentera,mykonos,paris,london,st_tropez,new_york,los_angeles,las_vegas',
        ],
    ];

    /**
     * Get specialties by a given region
     */
    public static function getSpecialtiesByRegion(string $regionId): Collection
    {
        return self::all()
            ->filter(fn ($specialty) => in_array($regionId, explode(',', $specialty->regions)))
            ->pluck('name', 'id');
    }
}
