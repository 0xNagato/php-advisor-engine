<?php

namespace App\Models;

use App\Casts\VenueContactCollection;
use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Enums\VenueType;
use App\Models\Traits\HasEarnings;
use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Throwable;

class Venue extends Model
{
    use HasEarnings, HasFactory, LogsActivity;

    public const int DEFAULT_TABLES = 10;

    public const int DEFAULT_START_HOUR = 11; // 11:00 AM

    public const int DEFAULT_END_HOUR = 23; // 11:00 PM

    public const int DEFAULT_PAYOUT_VENUE = 60;

    public const int DEFAULT_BOOKING_FEE = 200;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'address',
        'description',
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
        'images',
        'region',
        'timezone',
        'increment_fee',
        'non_prime_fee_per_head',
        'non_prime_type',
        'status',
        'cutoff_time',
        'daily_prime_bookings_cap',
        'daily_non_prime_bookings_cap',
        'no_wait',
        'venue_group_id',
        'is_omakase',
        'omakase_details',
        'omakase_concierge_fee',
        'cuisines',
        'neighborhood',
        'specialty',
        'venue_type',
        'advance_booking_window',
        'uses_covermanager',
        'covermanager_id',
        'covermanager_sync_enabled',
        'last_covermanager_sync',
        'tier',
    ];

    protected function casts(): array
    {
        return [
            'open_days' => 'array',
            'contacts' => VenueContactCollection::class,
            'non_prime_time' => 'array',
            'business_hours' => 'array',
            'party_sizes' => 'array',
            'status' => VenueStatus::class,
            'venue_type' => VenueType::class,
            'cutoff_time' => 'datetime',
            'daily_booking_cap' => 'integer',
            'cuisines' => 'array',
            'specialty' => 'array',
            'uses_covermanager' => 'boolean',
            'covermanager_sync_enabled' => 'boolean',
            'last_covermanager_sync' => 'datetime',
            'images' => 'array',
        ];
    }

    /**
     * Get a formatted neighborhood name
     */
    protected function formattedNeighborhood(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (blank($this->neighborhood)) {
                    return '';
                }

                try {
                    // Use the Neighborhood model to get the properly formatted name
                    $model = Neighborhood::query()->find($this->neighborhood);

                    return $model ? $model->name : '';
                } catch (Throwable) {
                    return '';
                }
            }
        );
    }

    /**
     * Get formatted region name
     */
    protected function formattedRegion(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (blank($this->region)) {
                    return '';
                }

                try {
                    // Use the Region model to get the properly formatted name
                    $model = Region::query()->find($this->region);

                    return $model ? $model->name : '';
                } catch (Throwable) {
                    return '';
                }
            }
        );
    }

    /**
     * Get a full formatted address
     */
    protected function formattedAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = [];

                if (filled($this->address)) {
                    $parts[] = $this->address;
                }

                $location = [];
                if (filled($this->formattedNeighborhood)) {
                    $location[] = $this->formattedNeighborhood;
                }

                if (filled($this->formattedRegion)) {
                    $location[] = $this->formattedRegion;
                }

                if (filled($location)) {
                    $parts[] = implode(', ', $location);
                }

                return $parts;
            }
        );
    }

    /**
     * Get the venue description with cite tags removed
     */
    protected function cleanDescription(): Attribute
    {
        return Attribute::make(get: function () {
            if (blank($this->description)) {
                return '';
            }
            // Remove all cite tags and their attributes
            $cleanDescription = preg_replace('/<\/?cite[^>]*>/', '', $this->description);

            return $cleanDescription;
        });
    }

    /**
     * Get formatted address with line breaks converted to HTML
     */
    protected function formattedAddressHtml(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->address ? nl2br(e($this->address)) : ''
        );
    }

    protected function tierLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->tier) {
                1 => 'Gold',
                2 => 'Silver',
                default => 'Standard',
            }
        );
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Venue $venue) {
            $venue->increment_fee = 50;

            // Generate base slug
            $baseSlug = Str::slug("{$venue->region}-{$venue->name}");
            $baseSlug = strtolower($baseSlug);

            // Find if any similar slugs exist and get the count
            $count = static::query()
                ->whereRaw('LOWER(slug) like ?', [$baseSlug.'%'])
                ->count();

            // If similar slugs exist, append the next number
            $venue->slug = $count ? "{$baseSlug}-".($count + 1) : $baseSlug;

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
                '10' => 10,
                '12' => 12,
                '14' => 14,
                '16' => 16,
                '18' => 18,
                '20' => 20,
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

    public function scopeActive($query)
    {
        return $query->where('status', VenueStatus::ACTIVE);
    }

    protected function logo(): Attribute
    {
        return Attribute::make(get: fn () => $this->logo_path
            ? Storage::disk('do')->url($this->logo_path)
            : 'https://ui-avatars.com/api/?background=312596&color=fff&name='.urlencode($this->name)
        );
    }

    protected function images(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Get the raw images array from the database
                $rawImages = $this->getRawOriginal('images');

                if (blank($rawImages)) {
                    return [];
                }

                // Parse the JSON if it's a string
                $images = is_string($rawImages)
                    ? json_decode($rawImages, true)
                    : $rawImages;

                if (! is_array($images)) {
                    return [];
                }

                return array_map(fn ($imagePath) => Storage::disk('do')->url($imagePath), $images);
            }
        );
    }

    protected function isAvailableForAdvanceBooking(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->advance_booking_window === null) {
                    return false;
                }

                $today = Carbon::now();
                $endDate = $today->copy()->addDays($this->advance_booking_window - 1);

                return $today->between($today, $endDate);
            }
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
     * @return HasMany<ScheduleWithBookingMV, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ScheduleWithBookingMV::class, 'venue_id');
    }

    public function scopeWithSchedulesForDate($query, string $date, int $partySize, string $startTime, string $endTime)
    {
        return $query->with([
            'schedules' => function ($query) use ($date, $partySize, $startTime, $endTime) {
                $query->where('booking_date', $date)
                    ->where('party_size', $partySize)
                    ->where('start_time', '>=', $startTime)
                    ->where('start_time', '<=', $endTime);
            },
            'schedules.timeSlots' => function ($query) use ($date) {
                $query->where('booking_date', $date);
            },
        ]);
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
     * @return HasManyThrough<Booking, ScheduleTemplate, $this>
     */
    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Booking::class, // The final model you want to access (Booking)
            ScheduleTemplate::class // The intermediate model (ScheduleTemplate)
        )->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED]);
    }

    /**
     * @return HasManyThrough<Booking, ScheduleTemplate, $this>
     */
    public function confirmedBookings(): HasManyThrough
    {
        return $this->hasManyThrough(Booking::class, ScheduleTemplate::class)
            ->whereNotNull('confirmed_at')
            ->whereIn('status',
                [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED, BookingStatus::PARTIALLY_REFUNDED]);
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
            ->setDescriptionForEvent(fn (string $eventName) => "Venue has been {$eventName}")
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<VenueGroup, $this>
     */
    public function venueGroup(): BelongsTo
    {
        return $this->belongsTo(VenueGroup::class);
    }

    /**
     * Get a formatted location string for display in modals
     */
    public function getFormattedLocation(): string
    {
        $neighborhood = null;
        $region = null;

        try {
            if (filled($this->neighborhood)) {
                $neighborhood = Neighborhood::query()->find($this->neighborhood);
            }

            if (filled($this->region)) {
                $region = Region::query()->find($this->region);
            }
        } catch (Throwable) {
            // Silently handle errors
        }

        $parts = [];

        if ($neighborhood) {
            $parts[] = $neighborhood->name;
        }

        if ($region) {
            $parts[] = $region->name;
        }

        return implode(', ', $parts);
    }

    /**
     * Get the booking platforms associated with the venue.
     *
     * @return HasMany<VenuePlatform, $this>
     */
    public function platforms(): HasMany
    {
        return $this->hasMany(VenuePlatform::class);
    }

    /**
     * Get a specific platform by type.
     */
    public function getPlatform(string $platformType): ?VenuePlatform
    {
        return $this->platforms()->where('platform_type', $platformType)->first();
    }

    /**
     * Check if venue has a specific platform enabled.
     */
    public function hasPlatform(string $platformType): bool
    {
        return $this->platforms()->where('platform_type', $platformType)
            ->where('is_enabled', true)->exists();
    }

    /**
     * Get the appropriate booking platform service for this venue.
     *
     * @return BookingPlatformInterface|null
     */
    public function getBookingPlatform()
    {
        $factory = app(BookingPlatformFactory::class);

        return $factory->getPlatformForVenue($this);
    }

    // For backward compatibility
    public function usesCoverManager(): bool
    {
        return $this->hasPlatform('covermanager');
    }

    /**
     * Get CoverManager service
     */
    public function coverManager()
    {
        if (! $this->usesCoverManager()) {
            return null;
        }

        return app(CoverManagerService::class);
    }

    /**
     * Sync this venue's availability with CoverManager for given date range
     *
     * @param  Carbon  $date  Starting date
     * @param  int  $days  Number of days to sync (defaults to 1 for backward compatibility)
     */
    public function syncCoverManagerAvailability(Carbon $date, int $days = 1): bool
    {
        // Check if venue has enabled CoverManager platform
        $platform = $this->getPlatform('covermanager');

        if (! $platform || ! $platform->is_enabled) {
            return false;
        }

        $coverManagerService = $this->coverManager();

        if (! $coverManagerService) {
            return false;
        }

        try {
            $endDate = $date->copy()->addDays($days - 1);

            // Make single bulk API call for entire date range
            $calendarData = $coverManagerService->checkAvailabilityCalendar(
                $this,
                $date,
                $endDate,
                'all',
                '1'
            );

            // Handle empty response or API errors
            if (empty($calendarData) || (isset($calendarData['resp']) && $calendarData['resp'] === 0)) {
                Log::warning("CoverManager calendar API returned no data for venue {$this->id}", [
                    'venue_id' => $this->id,
                    'date_range' => "{$date->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
                    'response' => $calendarData,
                ]);

                return false;
            }

            // Process each day in the range
            for ($i = 0; $i < $days; $i++) {
                $currentDate = $date->copy()->addDays($i);
                $dateKey = $currentDate->format('Y-m-d');

                // Get all schedule templates for this date
                $scheduleTemplates = $this->scheduleTemplates()
                    ->where('day_of_week', strtolower($currentDate->format('l')))
                    ->where('is_available', true)
                    ->get();

                // For each schedule template, check availability in bulk calendar data
                foreach ($scheduleTemplates as $template) {
                    // Check if VenueTimeSlot already exists
                    $existingSlot = VenueTimeSlot::query()
                        ->where('schedule_template_id', $template->id)
                        ->where('booking_date', $currentDate)
                        ->first();

                    // Skip if human-created override exists (check activity logs)
                    if ($existingSlot && $this->isHumanCreatedSlot($existingSlot)) {
                        continue;
                    }

                    // Parse CM bulk response to determine if venue has availability
                    $hasCmAvailability = $this->parseCalendarAvailabilityResponse(
                        $calendarData,
                        $dateKey,
                        $template
                    );

                    // Determine what prime_time should be based on CM availability
                    $shouldBePrime = ! $hasCmAvailability; // No CM availability = Prime

                    // Check if we need to create/update a venue time slot
                    // Only create one if it differs from the template default
                    if ($shouldBePrime !== $template->prime_time) {
                        // Create or update VenueTimeSlot to override the template default
                        $venueTimeSlot = VenueTimeSlot::query()->updateOrCreate(
                            [
                                'schedule_template_id' => $template->id,
                                'booking_date' => $currentDate,
                            ],
                            [
                                'prime_time' => $shouldBePrime,
                                'is_available' => $template->is_available,
                                'available_tables' => $template->available_tables,
                                'price_per_head' => $template->price_per_head ?? 0,
                                'minimum_spend_per_guest' => $template->minimum_spend_per_guest ?? 0,
                                'prime_time_fee' => $template->prime_time_fee ?? 0,
                            ]
                        );
                    } else {
                        // CM availability matches template default, remove any existing override
                        $existingSlot = VenueTimeSlot::query()
                            ->where('schedule_template_id', $template->id)
                            ->where('booking_date', $currentDate)
                            ->first();

                        if ($existingSlot) {
                            // Only delete if it's not human-created
                            if (! $this->isHumanCreatedSlot($existingSlot)) {
                                $existingSlot->delete();

                                // Log the removal
                                activity()
                                    ->performedOn($this)
                                    ->withProperties([
                                        'schedule_template_id' => $template->id,
                                        'booking_date' => $currentDate->format('Y-m-d'),
                                        'cm_availability' => $hasCmAvailability,
                                        'removed_override' => true,
                                        'template_prime_time' => $template->prime_time,
                                        'sync_method' => 'bulk_calendar',
                                    ])
                                    ->log('CoverManager availability synced');
                            }
                        }

                        continue; // Skip to next template since no override needed
                    }

                    // Log this as automated sync (only when we created/updated a slot)
                    if (isset($venueTimeSlot)) {
                        activity()
                            ->performedOn($this)
                            ->withProperties([
                                'schedule_template_id' => $template->id,
                                'booking_date' => $currentDate->format('Y-m-d'),
                                'cm_availability' => $hasCmAvailability,
                                'set_prime' => $shouldBePrime,
                                'venue_time_slot_id' => $venueTimeSlot->id,
                                'template_prime_time' => $template->prime_time,
                                'override_needed' => true,
                                'sync_method' => 'bulk_calendar',
                            ])
                            ->log('CoverManager availability synced');
                    }
                }
            }

            // Update last sync timestamp in platform configuration
            $platform->update(['last_synced_at' => now()]);

            return true;
        } catch (Throwable $e) {
            Log::error("Failed to sync venue {$this->id} availability with CoverManager", [
                'error' => $e->getMessage(),
                'venue_id' => $this->id,
                'venue_name' => $this->name,
                'date' => $date->format('Y-m-d'),
                'days' => $days,
            ]);

            return false;
        }
    }

    /**
     * Check if a VenueTimeSlot was created by a human (not automated sync)
     */
    protected function isHumanCreatedSlot(VenueTimeSlot $slot): bool
    {
        // Check if there are any activity logs for this slot that indicate human interaction
        $humanActions = [
            'override_update',
            'calendar_bulk_update',
            'make_day_prime',
            'make_day_non_prime',
            'mark_day_sold_out',
            'close_day',
            'set_price_per_head_for_day',
        ];

        return Activity::query()
            ->where('subject_type', static::class)
            ->where('subject_id', $this->id)
            ->whereJsonContains('properties->venue_time_slot_id', $slot->id)
            ->whereIn('description', $humanActions)
            ->exists();
    }

    /**
     * Parse CoverManager availability response to determine if venue has availability
     */
    protected function parseAvailabilityResponse(array $response, ScheduleTemplate $template): bool
    {
        // Handle empty response or API errors
        if (empty($response)) {
            return false;
        }

        // Check if response indicates API failure
        if (isset($response['resp']) && $response['resp'] === 0) {
            return false;
        }

        // Check if availability data exists
        if (! isset($response['availability']['people'])) {
            return false;
        }

        $partySize = (string) $template->party_size;
        $time = Carbon::parse($template->start_time)->format('H:i');

        // Check if this party size and time combination has availability
        return isset($response['availability']['people'][$partySize][$time]);
    }

    /**
     * Parse CoverManager calendar availability response to determine if venue has availability
     * for a specific date and schedule template
     */
    protected function parseCalendarAvailabilityResponse(array $response, string $dateKey, ScheduleTemplate $template): bool
    {
        // Handle empty response or API errors
        if (empty($response)) {
            return false;
        }

        // Check if response indicates API failure
        if (isset($response['resp']) && $response['resp'] === 0) {
            return false;
        }

        // Check if calendar data exists
        if (! isset($response['calendar'])) {
            return false;
        }

        // Check if data exists for this specific date
        if (! isset($response['calendar'][$dateKey])) {
            return false;
        }

        $dayData = $response['calendar'][$dateKey];
        $partySize = (string) $template->party_size;
        $time = Carbon::parse($template->start_time)->format('H:i');

        // Check availability in people array (party size specific)
        if (isset($dayData['people'][$partySize][$time])) {
            return true;
        }

        // Check availability in hours array (time specific, any party size)
        if (isset($dayData['hours'][$time])) {
            return true;
        }

        // Check if there's a generic "slots" availability for this date
        if (isset($dayData['slots']) && is_array($dayData['slots'])) {
            foreach ($dayData['slots'] as $slot) {
                if (isset($slot['availability']) && $slot['availability'] === true) {
                    return true;
                }
            }
        }

        // Check if time slot appears as a direct key with availability data
        if (isset($dayData[$time])) {
            $timeSlotData = $dayData[$time];
            if (isset($timeSlotData['availability']) && $timeSlotData['availability'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all platform reservations for this venue.
     *
     * @return HasMany<PlatformReservation, $this>
     */
    public function platformReservations(): HasMany
    {
        return $this->hasMany(PlatformReservation::class);
    }

    /**
     * Get CoverManager reservations for this venue.
     *
     * @return HasMany<PlatformReservation, $this>
     */
    public function coverManagerReservations(): HasMany
    {
        return $this->platformReservations()->where('platform_type', 'covermanager');
    }

    /**
     * Get Restoo reservations for this venue.
     *
     * @return HasMany<PlatformReservation, $this>
     */
    public function restooReservations(): HasMany
    {
        return $this->platformReservations()->where('platform_type', 'restoo');
    }

    /**
     * @return HasOne<VenueOnboarding, $this>
     */
    public function venueOnboarding(): HasOne
    {
        return $this->hasOne(VenueOnboarding::class, 'venue_group_id', 'venue_group_id')
            ->orWhere(function ($query) {
                $query->where('company_name', $this->name);
            });
    }
}
