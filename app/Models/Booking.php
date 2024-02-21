<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'schedule_id',
        'concierge_id',
        'guest_first_name',
        'guest_last_name',
        'guest_email',
        'guest_phone',
        'guest_count',
        'currency',
        'status',
        'total_fee',
        'booking_at',
        'payout_restaurant',
        'payout_charity',
        'payout_concierge',
        'payout_platform',
    ];

    protected $appends = [
        'restaurant_fee',
        'charity_fee',
        'concierge_fee',
        'platform_fee',
    ];

    protected $casts = [
        'booking_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Booking $booking) {
            $booking->uuid = (string)Str::uuid();
            $booking->total_fee = $booking->totalFee();
            $booking->payout_restaurant = $booking->schedule->restaurant->payout_restaurant;
            $booking->payout_charity = $booking->schedule->restaurant->payout_charity;
            $booking->payout_concierge = $booking->schedule->restaurant->payout_concierge;
            $booking->payout_platform = $booking->schedule->restaurant->payout_platform;
        });
    }

    public function totalFee(): int
    {
        $total_fee = $this->schedule->restaurant->booking_fee;

        if ($this->guest_count > 2) {
            $total_fee += 50 * ($this->guest_count - 2);
        }

        return $total_fee * 100;
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function getRestaurantFeeAttribute(): int
    {
        return $this->total_fee * $this->payout_restaurant / 100;
    }

    public function getCharityFeeAttribute(): int
    {
        return $this->total_fee * $this->payout_charity / 100;
    }

    public function getConciergeFeeAttribute(): int
    {
        return $this->total_fee * $this->payout_concierge / 100;
    }

    public function getPlatformFeeAttribute(): int
    {
        return $this->total_fee * $this->payout_platform / 100;
    }
}
