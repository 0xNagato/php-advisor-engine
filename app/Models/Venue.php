<?php

namespace App\Models;

use App\Data\VenueContactData;
use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Models\Traits\HasEarnings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\DataCollection;
use Throwable;

class Venue extends Model
{
    use HasEarnings, HasFactory, LogsActivity;

    public const int DEFAULT_TABLES = 0;

    public const int DEFAULT_START_HOUR = 11; // 11:00 AM

    public const int DEFAULT_END_HOUR = 22; // 10:00 PM

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'contact_phone',
        'payout_venue',
        'payout_charity',
        'payout_concierge',
        'payout_platform',
        'secondary_contact_phone',
        'primary_contact_name',
        'secondary_contact_name',
        'booking_fee',
        'open_days',
        'contacts',
        'is_suspended',
        'non_prime_time',
        'business_hours',
        'party_sizes',
        'minimum_spend',
        'logo_path',
        'region',
        'timezone',
        'increment_fee',
        'non_prime_fee_per_head',
        'non_prime_type',
        'status',
        'cutoff_time',
        'daily_prime_bookings_cap',
        'daily_non_prime_bookings_cap',
    ];

    protected function casts(): array
    {
        return [
            'open_days' => 'array',
            'contacts' => DataCollection::class.':'.VenueContactData::class,
            'non_prime_time' => 'array',
            'business_hours' => 'array',
            'party_sizes' => 'array',
            'status' => VenueStatus::class,
            'cutoff_time' => 'datetime',
            'daily_booking_cap' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Venue $venue) {
            $venue->slug = Str::slug("$venue->region-$venue->name");

            $venue->open_days = [
                'monday' => 'open',
                'tuesday' => 'open',
                'wednesday' => 'open',
                'thursday' => 'open',
                'friday' => 'open',
                'saturday' => 'open',
                'sunday' => 'open',
            ];

            $venue->party_sizes = [
                'Special Request' => 0,
                '2' => 2,
                '4' => 4,
                '6' => 6,
                '8' => 8,
            ];
        });

        static::created(static function (Venue $venue) {
            $venue->createDefaultSchedules();
        });
    }

    public function createDefaultSchedules(): void
    {
        $schedulesData = [];
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOfWeek as $dayOfWeek) {
            $startTime = Carbon::createFromTime();
            $endTime = Carbon::createFromTime(23, 59);

            while ($startTime->lessThanOrEqualTo($endTime)) {
                $isAvailable = $startTime->hour >= self::DEFAULT_START_HOUR && ($startTime->hour < self::DEFAULT_END_HOUR || ($startTime->hour === self::DEFAULT_END_HOUR && $startTime->minute < 30));

                foreach ($this->party_sizes as $partySize) {
                    $timeSlotStart = clone $startTime;

                    $schedulesData[] = [
                        'venue_id' => $this->id,
                        'start_time' => $timeSlotStart->format('H:i:s'),
                        'end_time' => $timeSlotStart->addMinutes(30)->format('H:i:s'),
                        'is_available' => $isAvailable,
                        'prime_time' => $isAvailable,
                        'available_tables' => $isAvailable ? self::DEFAULT_TABLES : 0,
                        'day_of_week' => $dayOfWeek,
                        'party_size' => $partySize,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $startTime->addMinutes(30);
            }
        }

        $this->scheduleTemplates()->insert($schedulesData);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereHas('user', function (Builder $query) {
            $query->whereNotNull('secured_at');
        })->whereIn('status', ['active', 'pending']);
    }

    protected function logo(): Attribute
    {
        return Attribute::make(get: fn () => $this->logo_path
            ? Storage::disk('do')->url($this->logo_path)
            : 'https://ui-avatars.com/api/?background=312596&color=fff&name='.urlencode($this->name)
        );
    }

    /**
     * @return HasMany<ScheduleTemplate, $this>
     */
    public function scheduleTemplates(): HasMany
    {
        return $this->hasMany(ScheduleTemplate::class);
    }

    /**
     * @return HasManyThrough<VenueTimeSlot, ScheduleTemplate, $this>
     */
    public function timeSlots(): HasManyThrough
    {
        return $this->hasManyThrough(VenueTimeSlot::class, ScheduleTemplate::class);
    }

    /**
     * Get the schedules for the venue.
     *
     * @return HasMany<ScheduleWithBooking, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ScheduleWithBooking::class, 'venue_id');
    }

    public function scopeWithSchedulesForDate($query, string $date, int $partySize, string $startTime, string $endTime)
    {
        return $query->with(['schedules' => function ($query) use ($date, $partySize, $startTime, $endTime) {
            $query->where('booking_date', $date)
                ->where('party_size', $partySize)
                ->where('start_time', '>=', $startTime)
                ->where('start_time', '<=', $endTime);
        }]);
    }

    /**
     * Get the user that owns the venue.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function inRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region', 'id');
    }

    /**
     * @return HasMany<SpecialPricingVenue, $this>
     */
    public function specialPricing(): HasMany
    {
        return $this->hasMany(SpecialPricingVenue::class);
    }

    /**
     * @return HasOneThrough<Partner, User, $this>
     */
    public function partnerReferral(): HasOneThrough
    {
        return $this->hasOneThrough(Partner::class, User::class, 'id', 'id', 'user_id', 'partner_referral_id');
    }

    /**
     * @return HasManyThrough<Booking>
     */
    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Booking::class, // The final model you want to access (Booking)
            ScheduleTemplate::class // The intermediate model (ScheduleTemplate)
        )->where('status', BookingStatus::CONFIRMED);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOperatingHours(): array
    {
        $earliestStartTime = $this->scheduleTemplates()
            ->where('is_available', true)
            ->min('start_time');

        $latestEndTime = $this->scheduleTemplates()
            ->where('is_available', true)
            ->max('end_time');

        return [
            'earliest_start_time' => $earliestStartTime,
            'latest_end_time' => $latestEndTime,
        ];
    }

    public function getDetailedSchedule(): array
    {
        $schedule = [];
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOfWeek as $day) {
            $daySchedule = $this->scheduleTemplates()
                ->where('day_of_week', $day)
                ->where('is_available', true)
                ->orderBy('start_time')
                ->get(['start_time', 'end_time', 'is_available', 'prime_time']);

            $schedule[$day] = $daySchedule->isEmpty() ? 'closed' : $daySchedule->toArray();
        }

        return $schedule;
    }

    /**
     * @throws Throwable
     */
    public function updateReferringPartner(int $newPartnerId): void
    {
        $partner = Partner::query()->find($newPartnerId);
        throw_if(! $partner || ! $partner->user,
            new RuntimeException('Invalid partner ID or associated user not found.'));

        $oldPartnerId = $this->user->partner_referral_id;
        $newUserId = $partner->user_id;

        DB::transaction(function () use ($newPartnerId, $newUserId, $oldPartnerId) {
            $this->user->update(['partner_referral_id' => $newPartnerId]);

            Referral::query()->where('user_id', $this->user_id)
                ->update(['referrer_id' => $newUserId]);

            // Log the partner change explicitly
            activity()
                ->performedOn($this)
                ->withProperties([
                    'action' => 'change_partner',
                    'previous_partner_id' => $oldPartnerId,
                    'new_partner_id' => $newPartnerId,
                ])
                ->log('Venue partner changed');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])  // Remove user.partner_referral_id since we'll handle it separately
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'updated' => $this->wasChanged('status')
                    ? 'Venue status changed'
                    : 'Venue updated',
                default => "Venue {$eventName}"
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
