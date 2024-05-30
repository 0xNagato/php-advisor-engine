<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @mixin IdeHelperConcierge
 */
class Concierge extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'hotel_name',
    ];

    protected $appends = [
        //
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate the payout percentage based on the amount sales.
     *
     * @return int The payout percentage.
     */
    public function getPayoutPercentageAttribute(): int
    {
        $sales = $this->sales_this_month;

        if ($sales >= 0 && $sales <= 20) {
            return 10;
        }

        if ($sales >= 21 && $sales <= 50) {
            return 12;
        }

        return 15;
    }

    /**
     * Get the amount confirmed bookings.
     *
     * @return int The amount confirmed bookings.
     */
    public function getSalesAttribute(): int
    {
        return $this->bookings()->where('status', BookingStatus::CONFIRMED)->count();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'concierge_id')
            ->where('status', BookingStatus::CONFIRMED);
    }

    /**
     * Get the amount confirmed bookings.
     *
     * @return int The amount confirmed bookings.
     */
    public function getSalesThisMonthAttribute(): int
    {
        return $this->bookings()
            ->where('status', BookingStatus::CONFIRMED)
            ->whereMonth('created_at', now()->month)
            ->count();
    }

    public function referringConcierge(): HasOneThrough
    {
        return $this->hasOneThrough(
            self::class,
            User::class,
            'id',
            'id',
            'user_id',
            'concierge_referral_id'
        );
    }

    public function concierges(): HasManyThrough
    {
        return $this->hasManyThrough(
            self::class,
            User::class,
            'concierge_referral_id',
        );
    }

    public function referrals(): HasManyThrough
    {
        return $this->hasManyThrough(
            Referral::class,
            User::class,
            'id', // Foreign key on the users table...
            'referrer_id', // Foreign key on the referrals table...
            'user_id', // Local key on the concierges table...
            'id' // Local key on the users table...
        );
    }
}
