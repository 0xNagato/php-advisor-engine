<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'restaurant_profile_id',
        'date',
        'start_time',
        'end_time',
    ];

    public function restaurantProfile(): BelongsTo
    {
        return $this->belongsTo(RestaurantProfile::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }
}
