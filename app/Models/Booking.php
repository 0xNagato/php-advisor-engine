<?php

namespace App\Models;

use App\Actions\Booking\CreateBooking;
use App\Data\Stripe\StripeChargeData;
use App\Enums\BookingStatus;
use App\Services\Booking\BookingCalculationService;
use App\Traits\FormatsPhoneNumber;
use App\Traits\HasImmutableBookingProperties;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Throwable;

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
        'ip_address',
        'is_prime',
        'no_show',
        'notes',
        'partner_concierge_id',
        'partner_venue_id',
        'platform_earnings',
        'resent_venue_confirmation_at',
        'reviewed_at',
        'reviewed_by',
        'risk_reasons',
        'risk_score',
        'risk_state',
        'schedule_template_id',
        'status',
        'stripe_charge',
        'stripe_charge_id',
        'tax',
        'tax_amount_in_cents',
        'total_fee',
        'user_agent',
        'venue_confirmed_at',
        'venue_earnings',
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
        'risk_score',
        'risk_state',
        'risk_reasons',
        'risk_metadata',
        'reviewed_at',
        'reviewed_by',
        'ip_address',
        'user_agent',
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
            return min($this->total_fee ?? 0, CreateBooking::MAX_TOTAL_FEE_CENTS); // Cap at 500 in any currency
        }

        $calculatedFee = $this->schedule->fee($this->guest_count);

        return min($calculatedFee, CreateBooking::MAX_TOTAL_FEE_CENTS); // Cap at 500 in any currency
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

    /**
     * Gross revenue flowing through PRIMA for this booking (in cents).
     * Prime: customer payment minus any refunds. Non-prime: absolute value of venue_earnings (venue payment).
     */
    protected function grossRevenue(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                // Match booking detail view logic:
                // Prime: total_fee; Non-prime: ABS(venue_earnings)
                if ($this->is_prime) {
                    return (int) $this->total_fee;
                }

                return (int) abs($this->venue_earnings);
            }
        );
    }

    /**
     * PRIMA net platform revenue (in cents), adjusted for refunds when applicable.
     */
    protected function primaNetRevenue(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->is_refunded_or_partially_refunded
                ? (int) $this->final_platform_earnings_total
                : (int) $this->platform_earnings
        );
    }

    /**
     * Human-friendly prime status label.
     */
    protected function primeStatusLabel(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->is_prime ? 'Prime' : 'Non-Prime');
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
            'risk_reasons' => 'array',
            'risk_metadata' => \App\Data\RiskMetadata::class,
            'reviewed_at' => 'datetime',
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

    protected function isNonPrimeBigGroup(): Attribute
    {
        return Attribute::make(
            get: fn () => ! $this->is_prime &&
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

    /**
     * Sync this booking to external booking platforms
     */
    public function syncToBookingPlatforms(): bool
    {
        // Skip if no venue
        if (! $this->venue) {
            return false;
        }

        // Get enabled platforms for the venue
        $platforms = $this->venue->platforms()->where('is_enabled', true)->get();

        if ($platforms->isEmpty()) {
            return false;
        }

        $success = false;

        // Process each platform
        foreach ($platforms as $platform) {
            try {
                switch ($platform->platform_type) {
                    case 'covermanager':
                        $success = $this->syncToCoverManager() || $success;
                        break;
                    case 'restoo':
                        $success = $this->syncToRestoo() || $success;
                        break;
                }
            } catch (Throwable $e) {
                // Log exception but continue with other platforms
                Log::error("Error syncing booking to {$platform->platform_type}", [
                    'booking_id' => $this->id,
                    'platform' => $platform->platform_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $success;
    }

    /**
     * Sync this booking to CoverManager when confirmed
     *
     * @deprecated Use syncToBookingPlatforms() instead for multiple platform support
     */
    public function syncToCoverManager(): bool
    {
        // Skip if already synced or if venue doesn't use CoverManager
        if (! $this->venue || ! $this->venue->usesCoverManager() || $this->status !== BookingStatus::CONFIRMED) {
            return false;
        }

        // Create or update CoverManager reservation record
        $platformReservation = PlatformReservation::query()
            ->where('booking_id', $this->id)
            ->where('platform_type', 'covermanager')
            ->first() ?? PlatformReservation::createFromBooking($this, 'covermanager');

        if (! $platformReservation) {
            return false;
        }

        return $platformReservation->syncToPlatform();
    }

    /**
     * Sync this booking to Restoo when confirmed
     */
    public function syncToRestoo(): bool
    {
        // Skip if venue doesn't use Restoo
        if (! $this->venue || ! $this->venue->hasPlatform('restoo') || $this->status !== BookingStatus::CONFIRMED) {
            return false;
        }

        // Create or update Restoo reservation record
        $platformReservation = PlatformReservation::query()
            ->where('booking_id', $this->id)
            ->where('platform_type', 'restoo')
            ->first() ?? PlatformReservation::createFromBooking($this, 'restoo');

        if (! $platformReservation) {
            return false;
        }

        return $platformReservation->syncToPlatform();
    }

    /**
     * Scope for bookings pending risk review
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopePendingRiskReview(Builder $query): Builder
    {
        return $query->whereNotNull('risk_state')
            ->whereNull('reviewed_at');
    }

    /**
     * Scope for bookings with soft risk hold
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeSoftRiskHold(Builder $query): Builder
    {
        return $query->where('risk_state', 'soft')
            ->whereNull('reviewed_at');
    }

    /**
     * Scope for bookings with hard risk hold
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeHardRiskHold(Builder $query): Builder
    {
        return $query->where('risk_state', 'hard')
            ->whereNull('reviewed_at');
    }

    /**
     * Check if booking is on risk hold
     */
    public function isOnRiskHold(): bool
    {
        return in_array($this->risk_state, ['soft', 'hard']) && ! $this->reviewed_at;
    }

    /**
     * Check if booking needs manual review
     */
    public function needsManualReview(): bool
    {
        return $this->isOnRiskHold();
    }

    /**
     * Get risk level label
     */
    public function getRiskLevel(): string
    {
        if (! $this->risk_score) {
            return 'unknown';
        }

        if ($this->risk_score < 30) {
            return 'low';
        } elseif ($this->risk_score < 70) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Get risk reasons as array
     * This accessor ensures PostgreSQL JSONB is properly decoded
     */
    public function getRiskReasonsArrayAttribute(): array
    {
        $reasons = $this->attributes['risk_reasons'] ?? null;

        if (!$reasons) {
            return [];
        }

        // If it's already an array, return it
        if (is_array($reasons)) {
            return $reasons;
        }

        // If it's a JSON string, decode it
        if (is_string($reasons)) {
            $decoded = json_decode($reasons, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<RiskAuditLog, $this>
     */
    public function riskAuditLogs(): HasMany
    {
        return $this->hasMany(RiskAuditLog::class);
    }
}
