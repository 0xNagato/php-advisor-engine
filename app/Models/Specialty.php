<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Sushi\Sushi;

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
            'regions' => 'ibiza',
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
