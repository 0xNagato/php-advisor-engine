<?php

namespace App\Models;

use App\Data\Stripe\StripeChargeData;
use App\Enums\BookingStatus;
use App\Services\Booking\BookingCalculationService;
use App\Traits\FormatsPhoneNumber;
use App\Traits\HasImmutableBookingProperties;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin IdeHelperBooking
 */
class Booking extends Model
{
    use FormatsPhoneNumber;
    use HasFactory;
    use HasImmutableBookingProperties;
    use LogsActivity;
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
        'stripe_payment_intent_id',
        'refunded_at',
        'refund_data',
        'refund_reason',
        'refunded_guest_count',
        'original_total',
        'total_refunded',
        'platform_earnings_refunded',
        'meta',
        'source',
        'device',
        'booking_at_utc',
    ];

    protected $appends = ['guest_name', 'local_formatted_guest_phone'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Booking $booking) {
            $booking->uuid = Str::uuid();
            $booking->total_fee = $booking->totalFee();

            if ($booking->booking_at) {
                $booking->booking_at_utc = Carbon::parse(
                    $booking->booking_at->format('Y-m-d H:i:s'),
                    $booking->venue->timezone
                )->setTimezone('UTC');
            }

            if ($booking->is_prime) {
                $booking->venue_earnings =
                    $booking->total_fee *
                    ($booking->venue->payout_venue / 100);
                $booking->concierge_earnings =
                    $booking->total_fee *
                    ($booking->concierge->payout_percentage / 100);
            }
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

        static::updating(static function (Booking $booking) {
            if ($booking->isDirty('booking_at') && $booking->booking_at) {
                $booking->booking_at_utc = Carbon::parse(
                    $booking->booking_at->format('Y-m-d H:i:s'),
                    $booking->venue->timezone
                )->setTimezone('UTC');
            }
        });

        static::created(static function (Booking $booking) {
            app(BookingCalculationService::class)->calculateEarnings($booking);
        });
    }

    /**
     * @return HasMany<Earning, $this>
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    public function totalFee(): int
    {
        if (! $this->booking_at || ! $this->schedule) {
            return $this->total_fee ?? 0;
        }

        return $this->schedule->fee($this->guest_count);
    }

    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED]);
    }

    public function scopeNoShow($query)
    {
        return $query->where('status', BookingStatus::NO_SHOW);
    }

    public function scopeConfirmedOrNoShow($query)
    {
        return $query->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::NO_SHOW]);
    }

    public function scopeRecentBookings($query)
    {
        return $query->whereIn('status', [
            BookingStatus::CONFIRMED,
            BookingStatus::NO_SHOW,
            BookingStatus::CANCELLED,
            BookingStatus::REFUNDED,
            BookingStatus::PARTIALLY_REFUNDED,
        ]);
    }

    /**
     * Scope query to filter by venue ID
     */
    public function scopeForVenue($query, int $venueId)
    {
        return $query->whereExists(function ($query) use ($venueId) {
            $query->select(\DB::raw(1))
                ->from('schedule_templates')
                ->where('schedule_templates.venue_id', $venueId)
                ->whereColumn('schedule_templates.id', 'bookings.schedule_template_id');
        });
    }

    /**
     * @return BelongsTo<ScheduleWithBookingMV, $this>
     */
    public function schedule(): BelongsTo
    {
        $relation = $this->belongsTo(ScheduleWithBookingMV::class, 'schedule_template_id', 'schedule_template_id');

        if ($this->booking_at) {
            $booking_date = $this->booking_at->format('Y-m-d');
            $booking_time = $this->booking_at->format('H:i:s');

            $relation->where(function ($query) use ($booking_date, $booking_time) {
                $query->whereDate('schedule_with_bookings.booking_at', $booking_date)
                    ->whereTime('schedule_with_bookings.booking_at', $booking_time);
            });
        }

        return $relation;
    }

    /**
     * @return HasOneThrough<Venue, ScheduleTemplate, $this>
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
     * @return BelongsTo<Concierge, $this>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partnerConcierge(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_concierge_id');
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partnerVenue(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_venue_id');
    }

    /**
     * @return BelongsTo<VipCode, $this>
     */
    public function vipCode(): BelongsTo
    {
        return $this->belongsTo(VipCode::class);
    }

    /**
     * @return HasMany<BookingCustomerReminderLog, $this>
     */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(BookingCustomerReminderLog::class, 'booking_id');
    }

    protected function guestName(): Attribute
    {
        return Attribute::make(get: fn () => $this->guest_first_name.' '.$this->guest_last_name);
    }

    protected function primeTime(): Attribute
    {
        return Attribute::make(get: fn () => $this->is_prime);
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
            'booking_at_utc' => 'datetime',
            'status' => BookingStatus::class,
            'stripe_charge' => StripeChargeData::class,
            'confirmed_at' => 'datetime',
            'clicked_at' => 'datetime',
            'venue_confirmed_at' => 'datetime',
            'resent_venue_confirmation_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refund_data' => 'array',
            'meta' => AsArrayObject::class,
        ];
    }

    protected function finalTotal(): Attribute
    {
        return Attribute::make(get: fn () => $this->total_fee - $this->total_refunded);
    }

    protected function finalPlatformEarningsTotal(): Attribute
    {
        return Attribute::make(get: fn () => $this->platform_earnings - $this->platform_earnings_refunded);
    }

    protected static function booted(): void
    {
        static::creating(function ($booking) {
            $booking->original_total = $booking->total_fee;
        });
    }

    protected function isConfirmed(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === BookingStatus::CONFIRMED
        );
    }

    protected function isRefunded(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === BookingStatus::REFUNDED
        );
    }

    protected function isRefundedOrPartiallyRefunded(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->status, [BookingStatus::REFUNDED, BookingStatus::PARTIALLY_REFUNDED])
        );
    }

    protected function isNonPrimeIbizaBigGroup(): Attribute
    {
        return Attribute::make(
            get: fn () => ! $this->is_prime &&
                $this->venue?->inRegion?->id === 'ibiza' &&
                $this->guest_count >= 8
        );
    }

    /**
     * @return HasMany<BookingModificationRequest, $this>
     */
    public function modificationRequests(): HasMany
    {
        return $this->hasMany(BookingModificationRequest::class);
    }

    public function hasActiveModificationRequest(): bool
    {
        return $this->modificationRequests()
            ->where('status', BookingModificationRequest::STATUS_PENDING)
            ->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function transferToConcierge(Concierge $newConcierge): void
    {
        throw_unless(in_array($this->status, [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED]),
            new InvalidArgumentException('Only confirmed bookings can be transferred.'));

        DB::transaction(function () use ($newConcierge) {
            $oldConciergeId = $this->concierge_id;
            $oldPlatformEarnings = $this->platform_earnings;

            $this->earnings()
                ->delete();

            $this->update([
                'concierge_id' => $newConcierge->id,
            ]);

            app(BookingCalculationService::class)->calculateEarnings($this);

            activity()
                ->performedOn($this)
                ->withProperties([
                    'old_concierge_id' => $oldConciergeId,
                    'new_concierge_id' => $newConcierge->id,
                    'booking_id' => $this->id,
                    'guest_name' => $this->guest_name,
                    'venue_name' => $this->venue->name,
                    'booking_time' => $this->booking_at->format('M d, Y h:i A'),
                    'transferred_by' => auth()->user()->name,
                    'old_platform_earnings' => $oldPlatformEarnings,
                    'new_platform_earnings' => $this->platform_earnings,
                ])
                ->log('Booking transferred to new concierge');
        });
    }
}
