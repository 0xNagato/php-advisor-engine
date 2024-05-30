<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPartner
 */
class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'percentage',
    ];

    /**
     * @return BelongsTo<User, \App\Models\Partner>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Booking>
     */
    public function conciergeBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'partner_concierge_id');
    }

    /**
     * @return HasMany<Booking>
     */
    public function restaurantBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'partner_restaurant_id');
    }

    public function scopeWithAllBookings($query)
    {
        return $query->with(['conciergeBookings', 'restaurantBookings']);
    }
}
