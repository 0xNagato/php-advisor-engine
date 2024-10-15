<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    /**
     * @return HasManyThrough<Referral>
     */
    public function referrals(): HasManyThrough
    {
        return $this->hasManyThrough(
            Referral::class,
            User::class,
            'id',
            'referrer_id',
            'user_id',
            'id'
        );
    }

    public function scopeWithAllBookings(Builder $query): Builder
    {
        return $query->with(['conciergeBookings', 'venueBookings']);
    }

    /**
     * @return HasManyThrough<Booking>
     */
    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Booking::class,
            User::class,
            'id',
            'partner_concierge_id',
            'user_id',
            'id'
        )->orWhere('bookings.partner_venue_id', $this->id);
    }

    /**
     * @return HasManyThrough<Earning>
     */
    public function earnings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Earning::class,
            Booking::class,
            'partner_concierge_id',
            'booking_id',
            'id',
            'id'
        )->orWhere('bookings.partner_venue_id', $this->id)
            ->whereIn('earnings.type', ['partner_concierge', 'partner_venue']);
    }
}
