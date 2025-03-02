<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

/**
 * @property string $id
 * @property string $name
 * @property string $description
 *
 * @mixin IdeHelperCuisine
 */
class Cuisine extends Model
{
    use Sushi;

    public $incrementing = false;

    protected $keyType = 'string';

    protected array $rows = [
        [
            'id' => 'american',
            'name' => 'American',
            'description' => 'Includes burgers, steaks, BBQ, and modern casual dining.',
        ],
        [
            'id' => 'chinese',
            'name' => 'Chinese',
            'description' => 'Covers dim sum, Cantonese, Szechuan, and other regional variations.',
        ],
        [
            'id' => 'french',
            'name' => 'French',
            'description' => 'Fine dining, pastries, and classic dishes like coq au vin and escargot.',
        ],
        [
            'id' => 'indian',
            'name' => 'Indian',
            'description' => 'Known for rich curries, biryanis, and tandoori dishes.',
        ],
        [
            'id' => 'italian',
            'name' => 'Italian',
            'description' => 'Includes pizza, pasta, risotto, and other comfort foods. Always a top choice.',
        ],
        [
            'id' => 'japanese',
            'name' => 'Japanese',
            'description' => 'Sushi, ramen, tempura, and teppanyaki are highly sought-after.',
        ],
        [
            'id' => 'korean',
            'name' => 'Korean',
            'description' => 'Gaining popularity with BBQ, bibimbap, and street food like tteokbokki.',
        ],
        [
            'id' => 'mediterranean',
            'name' => 'Mediterranean',
            'description' => 'A mix of Greek, Lebanese, and Middle Eastern cuisine, including hummus, kebabs, and mezze.',
        ],
        [
            'id' => 'mexican',
            'name' => 'Mexican',
            'description' => 'Tacos, enchiladas, and fresh flavors make this a favorite worldwide.',
        ],
        [
            'id' => 'thai',
            'name' => 'Thai',
            'description' => 'Spicy, aromatic dishes like Pad Thai, green curry, and tom yum soup.',
        ],
        [
            'id' => 'vegan',
            'name' => 'Vegan',
            'description' => 'A cuisine that excludes all animal products.',
        ],
        [
            'id' => 'gluten_free',
            'name' => 'Gluten-Free',
            'description' => 'A cuisine that excludes gluten-containing ingredients.',
        ],
    ];

    /**
     * Scope query to only include specific cuisines
     */
    public function scopeFilter(Builder $query, array $cuisines): Builder
    {
        return $query->whereIn('id', $cuisines);
    }

    /**
     * Get a cuisine by ID
     */
    public static function findById(string $id): ?self
    {
        return self::query()->firstWhere('id', $id);
    }

    /**
     * Get all cuisine names as an associative array
     *
     * @return array<string, string>
     */
    public static function getNamesList(): array
    {
        return self::query()->pluck('name', 'id')->toArray();
    }
}
