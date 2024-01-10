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
        'concierge_user_id',
        'guest_user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_count',
        'total_fee',
        'currency',
        'status',
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
