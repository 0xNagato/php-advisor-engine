<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialPricingRestaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'date',
        'fee',
    ];

    protected $casts = [

    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
