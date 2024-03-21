<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
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
        'total_fee',
        'booking_at',
        'stripe_charge',
        'stripe_charge_id',
        'status',
        'partner_concierge_id',
        'partner_restaurant_id',
        'confirmed_at',
        'clicked_at',
        'concierge_referral_type',
    ];

    protected $appends = [
        'guest_name',
    ];

    protected $casts = [
        'booking_at' => 'datetime',
        'status' => BookingStatus::class,
        'stripe_charge' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Booking $booking) {
            $booking->uuid = Str::uuid();
        });

        static::saving(function (Booking $booking) {
            $booking->total_fee = $booking->totalFee();

            if ($booking->concierge->user->partner_referral_id) {
                $booking->partner_concierge_id = $booking->concierge->user->partner_referral_id;
            }

            if ($booking->schedule->restaurant->user->partner_referral_id) {
                $booking->partner_restaurant_id = $booking->schedule->restaurant->user->partner_referral_id;
            }

            // $payouts = $booking->calculatePayouts();
            // $booking->restaurant_earnings = $payouts['restaurant'];
            // $booking->concierge_earnings = $payouts['concierge'];
            // $booking->charity_earnings = $payouts['charity'];
            // $booking->platform_earnings = $payouts['platform'];
            // $booking->partner_concierge_fee = $payouts['partner_concierge'];
            // $booking->partner_restaurant_fee = $payouts['partner_restaurant'];
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

    public function scopeConfirmed($query)
    {
        return $query->where('status', BookingStatus::CONFIRMED);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function restaurant(): HasOneThrough
    {
        return $this->hasOneThrough(
            Restaurant::class,
            Schedule::class,
            'id',
            'id',
            'schedule_id',
            'restaurant_id'
        );
    }

    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    public function partnerConcierge(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_concierge_id');
    }

    public function partnerRestaurant(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_restaurant_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function getGuestNameAttribute(): string
    {
        return $this->guest_first_name.' '.$this->guest_last_name;
    }

    public function getPartnerEarningsAttribute()
    {
        $earnings = 0;

        if ($this->partnerConcierge && $this->concierge->user->partner_referral_id === $this->partnerConcierge->id) {
            $earnings += $this->partner_concierge_fee;
        }

        if ($this->partnerRestaurant && $this->schedule->restaurant->user->partner_referral_id === $this->partnerRestaurant->id) {
            $earnings += $this->partner_restaurant_fee;
        }

        return $earnings;
    }
}
