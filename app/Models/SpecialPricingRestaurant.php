<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSpecialPricingRestaurant
 */
class SpecialPricingRestaurant extends Model
{
    protected $fillable = [
        'restaurant_id',
        'date',
        'fee',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
