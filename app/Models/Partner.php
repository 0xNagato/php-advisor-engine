<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'percentage',
    ];

    /**
     * @return BelongsTo<User, Partner>
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
    public function venueBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'partner_venue_id');
    }

    public function scopeWithAllBookings($query)
    {
        return $query->with(['conciergeBookings', 'venueBookings']);
    }
}
