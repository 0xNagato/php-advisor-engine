<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

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

    /**
     * @return BelongsTo<User, Concierge>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate the payout percentage based on the amount sales.
     */
    protected function payoutPercentage(): Attribute
    {
        return Attribute::make(get: function () {
            $sales = $this->sales_this_month;
            if ($sales >= 0 && $sales <= 20) {
                return 10;
            }
            if ($sales >= 21 && $sales <= 50) {
                return 12;
            }

            return 15;
        });
    }

    /**
     * Get the amount confirmed bookings.
     */
    protected function sales(): Attribute
    {
        return Attribute::make(get: fn () => $this->bookings()->where('status', BookingStatus::CONFIRMED)->count());
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'concierge_id')
            ->where('status', BookingStatus::CONFIRMED);
    }

    /**
     * Get the amount confirmed bookings.
     */
    protected function salesThisMonth(): Attribute
    {
        return Attribute::make(get: fn () => $this->bookings()
            ->where('status', BookingStatus::CONFIRMED)
            ->whereMonth('created_at', now()->month)
            ->count());
    }

    /**
     * @return HasOneThrough<Concierge>
     */
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

    /**
     * @return HasManyThrough<Concierge>
     */
    public function concierges(): HasManyThrough
    {
        return $this->hasManyThrough(
            self::class,
            User::class,
            'concierge_referral_id',
        );
    }

    /**
     * @return HasManyThrough<Referral>
     */
    public function referrals(): HasManyThrough
    {
        return $this->hasManyThrough(
            Referral::class,
            User::class,
            'id', // Foreign key on the user's table...
            'referrer_id', // Foreign key on the referral table...
            'user_id', // Local key on the concierge table...
            'id' // Local key on the user's table...
        );
    }

    /**
     * Description
     *
     * @return HasMany<VIPCode>
     */
    public function vipCodes(): HasMany
    {
        return $this->hasMany(VIPCode::class);
    }

    /**
     * @return HasManyThrough<Earning>
     */
    public function earnings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Earning::class,
            User::class,
            'id', // Foreign key on the user's table
            'user_id', // Foreign key on the earning's table
            'user_id', // Local key on the concierge's table
            'id'// Local key on the user's table...
        );
    }
}
