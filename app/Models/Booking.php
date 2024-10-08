<?php

namespace App\Models;

use App\Data\Stripe\StripeChargeData;
use App\Enums\BookingStatus;
use App\Services\Booking\BookingCalculationService;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Booking extends Model
{
    use FormatsPhoneNumber;
    use HasFactory;
    use Notifiable;

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
        'partner_venue_id',
        'platform_earnings',
        'resent_venue_confirmation_at',
        'venue_confirmed_at',
        'venue_earnings',
        'schedule_template_id',
        'status',
        'stripe_charge',
        'stripe_charge_id',
        'tax',
        'tax_amount_in_cents',
        'total_fee',
        'total_with_tax_in_cents',
        'vip_code_id',
    ];

    protected $appends = ['guest_name', 'local_formatted_guest_phone'];

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
                $booking->venue_earnings =
                    $booking->total_fee *
                    ($booking->venue->payout_venue / 100);
                $booking->concierge_earnings =
                    $booking->total_fee *
                    ($booking->concierge->payout_percentage / 100);
            }
        });

        static::created(static function (Booking $booking) {
            app(BookingCalculationService::class)->calculateEarnings($booking);
        });
    }

    /**
     * @return HasMany<Earning>
     */
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

    /**
     * @return HasOneThrough<Venue>
     */
    public function venue(): HasOneThrough
    {
        return $this->hasOneThrough(
            Venue::class,
            ScheduleTemplate::class,
            'id',
            'id',
            'schedule_template_id',
            'venue_id'
        );
    }

    /**
     * @return BelongsTo<Concierge, Booking>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * @return BelongsTo<Partner, Booking>
     */
    public function partnerConcierge(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_concierge_id');
    }

    /**
     * @return BelongsTo<Partner, Booking>
     */
    public function partnerVenue(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_venue_id');
    }

    /**
     * @return BelongsTo<VipCode, Booking>
     */
    public function vipCode(): BelongsTo
    {
        return $this->belongsTo(VipCode::class);
    }

    protected function guestName(): Attribute
    {
        return Attribute::make(get: fn () => $this->guest_first_name.' '.$this->guest_last_name);
    }

    protected function primeTime(): Attribute
    {
        return Attribute::make(get: fn () => $this->schedule->prime_time);
    }

    protected function localFormattedGuestPhone(): Attribute
    {
        return Attribute::make(get: fn () => $this->getLocalFormattedPhoneNumber($this->guest_phone));
    }

    public static function calculateNonPrimeEarnings(Booking $booking, $reconfirm = false): void
    {
        app(BookingCalculationService::class)->calculateNonPrimeEarnings($booking);

        if ($reconfirm) {
            $booking->update([
                'no_show' => false,
                'status' => BookingStatus::CONFIRMED,
            ]);
        }
    }

    public static function reverseNonPrimeEarnings(Booking $booking): void
    {
        $booking->earnings()->delete();

        $booking->update([
            'concierge_earnings' => 0,
            'venue_earnings' => 0,
            'platform_earnings' => 0,
            'no_show' => true,
            'status' => BookingStatus::NO_SHOW,
        ]);
    }

    protected function casts(): array
    {
        return [
            'booking_at' => 'datetime',
            'status' => BookingStatus::class,
            'stripe_charge' => StripeChargeData::class,
            'confirmed_at' => 'datetime',
            'clicked_at' => 'datetime',
            'venue_confirmed_at' => 'datetime',
            'resent_venue_confirmation_at' => 'datetime',
        ];
    }
}
