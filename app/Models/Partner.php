<?php

namespace App\Models;

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

    /**
     * @return HasMany<Booking>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'partner_concierge_id')
            ->orWhere('partner_venue_id', $this->id);
    }

    /**
     * @return HasManyThrough<Earning>
     */
    public function earnings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Earning::class,
            Booking::class,
            'partner_concierge_id', // local key on bookings table
            'booking_id', // local key on earnings table
            'id', // local key on partners table
            'id' // local key on bookings table
        )->orWhere('bookings.partner_venue_id', $this->id)
            ->whereIn('earnings.type', ['partner_concierge', 'partner_venue']);
    }
}
