<?php

namespace App\Models;

use App\Data\Stripe\StripeChargeData;
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
        'restaurant_confirmed_at',
        'resent_restaurant_confirmation_at',
        'tax',
        'tax_amount_in_cents',
        'city',
        'total_with_tax_in_cents',
        'invoice_path',
    ];

    protected $appends = [
        'guest_name',
    ];

    protected $casts = [
        'booking_at' => 'datetime',
        'status' => BookingStatus::class,
        'stripe_charge' => StripeChargeData::class,
        'confirmed_at' => 'datetime',
        'clicked_at' => 'datetime',
        'restaurant_confirmed_at' => 'datetime',
        'resent_restaurant_confirmation_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Booking $booking) {
            $booking->uuid = Str::uuid();
        });

        static::saving(function (Booking $booking) {
            $booking->total_fee = $booking->totalFee();

            $booking->restaurant_earnings = $booking->total_fee * ($booking->restaurant->payout_restaurant / 100);
            $booking->concierge_earnings = $booking->total_fee * ($booking->concierge->payout_percentage / 100);

            $remainder = $booking->total_fee - $booking->restaurant_earnings - $booking->concierge_earnings;

            if ($booking->concierge->user->partner_referral_id) {
                $booking->partner_concierge_id = $booking->concierge->user->partner_referral_id;
                $booking->partner_concierge_fee = $remainder * ($booking->partnerConcierge->percentage / 100);
                $remainder -= $booking->partner_concierge_fee;
            }

            if ($booking->restaurant->user->partner_referral_id) {
                $booking->partner_restaurant_id = $booking->restaurant->user->partner_referral_id;
                $booking->partner_restaurant_fee = $remainder * ($booking->partnerRestaurant->percentage / 100);
                $remainder -= $booking->partner_restaurant_fee;
            }

            $booking->platform_earnings = $remainder;
        });
    }

    public function totalFee(): int
    {
        $specialPrice = $this->schedule->restaurant->specialPricing()
            ->where('date', $this->booking_at->format('Y-m-d'))
            ->first()
            ->fee ?? $this->schedule->restaurant->booking_fee;

        $extraGuestFee = max(0, $this->guest_count - 2) * 50;

        return ($specialPrice + $extraGuestFee) * 100;
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
