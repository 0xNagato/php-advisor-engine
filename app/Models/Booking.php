<?php

namespace App\Models;

use App\Data\Stripe\StripeChargeData;
use App\Enums\BookingStatus;
use App\Traits\FormatsPhoneNumber;
use AssertionError;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentry;

class Booking extends Model
{
    use FormatsPhoneNumber;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_at',
        'city',
        'clicked_at',
        'concierge_earnings',
        'concierge_id',
        'concierge_referral_type',
        'confirmed_at',
        'currency',
        'guest_count',
        'guest_email',
        'guest_first_name',
        'guest_last_name',
        'guest_phone',
        'invoice_path',
        'is_prime',
        'no_show',
        'notes',
        'partner_concierge_id',
        'partner_restaurant_id',
        'platform_earnings',
        'resent_restaurant_confirmation_at',
        'restaurant_confirmed_at',
        'restaurant_earnings',
        'schedule_template_id',
        'status',
        'stripe_charge',
        'stripe_charge_id',
        'tax',
        'tax_amount_in_cents',
        'total_fee',
        'total_with_tax_in_cents',
    ];

    protected $appends = ['guest_name', 'local_formatted_guest_phone'];

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

        static::creating(static function (Booking $booking) {
            $booking->uuid = Str::uuid();
        });

        static::updated(static function (Booking $booking) {
            if (
                $booking->status === BookingStatus::CONFIRMED &&
                $booking->wasChanged('status')
            ) {
                DB::table('earnings')
                    ->where('booking_id', $booking->id)
                    ->update(['confirmed_at' => now()]);
            }

            if (
                $booking->status === BookingStatus::CANCELLED &&
                $booking->wasChanged('status')
            ) {
                $booking->earnings()->delete();
            }
        });

        static::saving(static function (Booking $booking) {
            $booking->total_fee = $booking->totalFee();

            if ($booking->is_prime) {
                $booking->restaurant_earnings =
                    $booking->total_fee *
                    ($booking->restaurant->payout_restaurant / 100);
                $booking->concierge_earnings =
                    $booking->total_fee *
                    ($booking->concierge->payout_percentage / 100);
            }
        });

        static::created(static function (Booking $booking) {
            if (! $booking->is_prime && $booking->restaurant->non_prime_type === 'paid') {
                static::calculateNonPrimeEarnings($booking);

                return;
            }

            DB::transaction(static function () use ($booking) {
                $remainder =
                    $booking->total_fee -
                    $booking->restaurant_earnings -
                    $booking->concierge_earnings;
                $platform = $remainder;

                $restaurant_earnings = $booking->restaurant_earnings;
                $concierge_earnings = $booking->concierge_earnings;
                $concierge_referral_level_1_earnings = 0;
                $concierge_referral_level_2_earnings = 0;
                $restaurant_partner_earnings = 0;
                $concierge_partner_earnings = 0;

                Earning::create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->restaurant->user->id,
                    'type' => 'restaurant',
                    'amount' => $booking->restaurant_earnings,
                    'currency' => $booking->currency,
                    'percentage' => $booking->restaurant->payout_restaurant,
                    'percentage_of' => 'total_fee',
                ]);

                Earning::create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->concierge->user->id,
                    'type' => 'concierge',
                    'amount' => $booking->concierge_earnings,
                    'currency' => $booking->currency,
                    'percentage' => $booking->concierge->payout_percentage,
                    'percentage_of' => 'total_fee',
                ]);

                if ($booking->concierge->referringConcierge) {
                    $user_id =
                        $booking->concierge->referringConcierge->user->id;
                    $referralPercentage = 10;
                    $amount = $remainder * ($referralPercentage / 100);
                    $platform -= $amount;
                    $concierge_referral_level_1_earnings = $amount;

                    Earning::create([
                        'booking_id' => $booking->id,
                        'user_id' => $user_id,
                        'type' => 'concierge_referral_1',
                        'amount' => $amount,
                        'currency' => $booking->currency,
                        'percentage' => $referralPercentage,
                        'percentage_of' => 'platform',
                    ]);
                }

                if (
                    $booking->concierge->referringConcierge &&
                    $booking->concierge->referringConcierge->referringConcierge
                ) {
                    $user_id =
                        $booking->concierge->referringConcierge
                            ->referringConcierge->user->id;
                    $referralPercentage = 5;
                    $amount = $remainder * ($referralPercentage / 100);
                    $platform -= $amount;
                    $concierge_referral_level_2_earnings = $amount;

                    Earning::create([
                        'booking_id' => $booking->id,
                        'user_id' => $user_id,
                        'type' => 'concierge_referral_2',
                        'amount' => $amount,
                        'currency' => $booking->currency,
                        'percentage' => $referralPercentage,
                        'percentage_of' => 'platform',
                    ]);
                }

                // Calculate partner's fees based on initial platform earnings
                if ($booking->concierge->user->partner_referral_id) {
                    $booking->partner_concierge_id =
                        $booking->concierge->user->partner_referral_id;
                    $booking->partner_concierge_fee =
                        $platform *
                        ($booking->partnerConcierge->percentage / 100);
                    $concierge_partner_earnings =
                        $booking->partner_concierge_fee;

                    $earning = Earning::create([
                        'booking_id' => $booking->id,
                        'user_id' => Partner::find(
                            $booking->concierge->user->partner_referral_id
                        )->user_id,
                        'type' => 'partner_concierge',
                        'amount' => $booking->partner_concierge_fee,
                        'currency' => $booking->currency,
                        'percentage' => $booking->partnerConcierge->percentage,
                        'percentage_of' => 'remainder',
                    ]);

                    app(Logger::class)->info('partner_concierge Earning created', [
                        'partner_concierge_id' => $booking->partner_concierge_id,
                        'partner_id' => $earning->user_id,
                        'earning' => $earning,
                        'booking' => $booking,
                    ]);
                }

                if ($booking->restaurant->user->partner_referral_id) {
                    $booking->partner_restaurant_id =
                        $booking->restaurant->user->partner_referral_id;
                    $booking->partner_restaurant_fee =
                        $platform *
                        ($booking->partnerRestaurant->percentage / 100);
                    $restaurant_partner_earnings =
                        $booking->partner_restaurant_fee;

                    Earning::create([
                        'booking_id' => $booking->id,
                        'user_id' => Partner::find(
                            $booking->restaurant->user->partner_referral_id
                        )->user_id,
                        'type' => 'partner_restaurant',
                        'amount' => $booking->partner_restaurant_fee,
                        'currency' => $booking->currency,
                        'percentage' => $booking->partnerRestaurant->percentage,
                        'percentage_of' => 'remainder',
                    ]);
                }

                // Deduct partner's fees from platform earnings
                $platform -=
                    $booking->partner_concierge_fee +
                    $booking->partner_restaurant_fee;

                $platform_earnings = $platform;

                $totalLocal =
                    $restaurant_earnings +
                    $concierge_earnings +
                    $concierge_referral_level_1_earnings +
                    $concierge_referral_level_2_earnings +
                    $restaurant_partner_earnings +
                    $concierge_partner_earnings +
                    $platform_earnings;

                try {
                    assert(
                        (int) $totalLocal === (int) $booking->total_fee,
                        'The sum of all earnings does not equal the total fee.'
                    );
                    $booking->platform_earnings = $platform;
                    $booking->save();
                } catch (AssertionError $e) {
                    EarningError::create([
                        'booking_id' => $booking->id,
                        'error_message' => $e->getMessage(),
                        'restaurant_earnings' => $restaurant_earnings,
                        'concierge_earnings' => $concierge_earnings,
                        'concierge_referral_level_1_earnings' => $concierge_referral_level_1_earnings,
                        'concierge_referral_level_2_earnings' => $concierge_referral_level_2_earnings,
                        'restaurant_partner_earnings' => $restaurant_partner_earnings,
                        'concierge_partner_earnings' => $concierge_partner_earnings,
                        'platform_earnings' => $platform_earnings,
                        'total_local' => $restaurant_earnings +
                            $concierge_earnings +
                            $concierge_referral_level_1_earnings +
                            $concierge_referral_level_2_earnings +
                            $restaurant_partner_earnings +
                            $concierge_partner_earnings +
                            $platform_earnings,
                        'total_fee' => $booking->total_fee,
                    ]);

                    if (app()->environment('production')) {
                        Sentry\captureException($e);
                    }
                }
            });
        });
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    public function totalFee(): int
    {
        return $this->schedule->fee($this->guest_count);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', BookingStatus::CONFIRMED);
    }

    public function scopeNoShow($query)
    {
        return $query->where('status', BookingStatus::NO_SHOW);
    }

    public function scopeConfirmedOrNoShow($query)
    {
        return $query->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::NO_SHOW]);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ScheduleWithBooking::class, 'schedule_template_id', 'schedule_template_id')
            ->whereColumn('booking_at', 'schedule_with_bookings.booking_at');
    }

    public function restaurant(): HasOneThrough
    {
        return $this->hasOneThrough(
            Restaurant::class,
            ScheduleTemplate::class,
            'id',
            'id',
            'schedule_template_id',
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

    public function getGuestNameAttribute(): string
    {
        return $this->guest_first_name.' '.$this->guest_last_name;
    }

    public function getPrimeTimeAttribute(): bool
    {
        return $this->schedule->prime_time;
    }

    public function getLocalFormattedGuestPhoneAttribute(): string
    {
        return $this->getLocalFormattedPhoneNumber($this->guest_phone);
    }

    public static function calculateNonPrimeEarnings(Booking $booking, $reconfirm = false): void
    {
        $fee = $booking->restaurant->non_prime_fee_per_head * $booking->guest_count;
        $concierge_earnings = $fee * 0.90;
        $platform_concierge = $fee * 0.10;
        $platform_restaurant = $fee * 0.07;
        $platform_earnings = $platform_concierge + $platform_restaurant;
        $restaurant_earnings = ($concierge_earnings + $platform_earnings) * -1;

        Earning::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->restaurant->user->id,
            'type' => 'restaurant_paid',
            'amount' => $restaurant_earnings * 100,
            'currency' => $booking->currency,
            'percentage' => -100,
            'percentage_of' => 'concierge_bounty',
        ]);

        Earning::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->concierge->user->id,
            'type' => 'concierge_bounty',
            'amount' => $concierge_earnings * 100,
            'currency' => $booking->currency,
            'percentage' => 90,
            'percentage_of' => 'concierge_bounty',
        ]);

        if ($reconfirm) {
            $booking->update([
                'no_show' => false,
                'status' => BookingStatus::CONFIRMED,
            ]);
        }

        $booking->update([
            'concierge_earnings' => $concierge_earnings * 100,
            'restaurant_earnings' => $restaurant_earnings * 100,
            'platform_earnings' => $platform_earnings * 100,
        ]);
    }

    public static function reverseNonPrimeEarnings(Booking $booking): void
    {
        $booking->earnings()->delete();

        $booking->update([
            'concierge_earnings' => 0,
            'restaurant_earnings' => 0,
            'platform_earnings' => 0,
            'no_show' => true,
            'status' => BookingStatus::NO_SHOW,
        ]);
    }
}
