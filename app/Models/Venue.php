<?php

namespace App\Models;

use App\Casts\VenueContactCollection;
use App\Data\GooglePlaceData;
use App\Data\VenueMetadata;
use App\Enums\BookingStatus;
use App\Enums\VenueStatus;
use App\Enums\VenueType;
use App\Models\Traits\HasEarnings;
use App\Services\CoverManagerService;
use App\Services\GooglePlacesImageUploader;
use App\Services\GooglePlacesToCuisineMapper;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Throwable;

/**
 * @mixin IdeHelperVenue
 */
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
        'tier_position',
        'vat',
        'metadata',
        'latitude',
        'longitude',
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
            'metadata' => VenueMetadata::class,
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

                return array_map(
                    fn ($imagePath) => (
                        str_contains($imagePath, 'prima-bucket.nyc3.digitaloceanspaces.com')
                            ? $imagePath
                            : Storage::disk('do')->url($imagePath)
                    ),
                    $images
                );
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

    /**
     * Update metadata from Google Places data.
     *
     * @return $this
     */
    public function updateMetadataFromGoogle(GooglePlaceData $placeData, bool $skipPhotos = false): self
    {
        // Build attributes array from boolean fields
        $attributes = [];
        if ($placeData->servesCocktails !== null) {
            $attributes['serves_cocktails'] = $placeData->servesCocktails;
        }
        if ($placeData->servesWine !== null) {
            $attributes['serves_wine'] = $placeData->servesWine;
        }
        if ($placeData->servesBeer !== null) {
            $attributes['serves_beer'] = $placeData->servesBeer;
        }
        if ($placeData->outdoorSeating !== null) {
            $attributes['outdoor_seating'] = $placeData->outdoorSeating;
        }
        if ($placeData->liveMusic !== null) {
            $attributes['live_music'] = $placeData->liveMusic;
        }

        // Get existing google photo URLs to track what we've already processed
        $existingGooglePhotoUrls = $this->metadata?->googlePhotoUrls ?? [];

        $this->metadata = VenueMetadata::from([
            'rating' => $placeData->rating ?? $this->metadata?->rating,
            'priceLevel' => $placeData->priceLevel ?? $this->metadata?->priceLevel,
            'reviewCount' => $placeData->userRatingsTotal ?? $this->metadata?->reviewCount,
            'googlePlaceId' => $placeData->placeId ?? $this->metadata?->googlePlaceId,
            'googleDescription' => $this->metadata?->googleDescription,
            'googleEditorialSummary' => $this->metadata?->googleEditorialSummary,
            'googleGenerativeSummary' => $this->metadata?->googleGenerativeSummary,
            'googleTypes' => $placeData->types ?? $this->metadata?->googleTypes,
            'googlePrimaryType' => $placeData->primaryType ?? $this->metadata?->googlePrimaryType,
            'googleAttributes' => ! empty($attributes) ? $attributes : $this->metadata?->googleAttributes,
            'googlePhotoUrls' => $existingGooglePhotoUrls, // Will be updated if photos are processed
            'lastSyncedAt' => now()->toISOString(),
        ]);

        // Map Google Places data to existing cuisine and specialty fields
        $mapper = app(GooglePlacesToCuisineMapper::class);

        // Update cuisines - merge with existing or set if empty
        $mappedCuisines = $mapper->mapToCuisines($placeData->types);
        if (! empty($mappedCuisines)) {
            $existingCuisines = is_array($this->cuisines) ? $this->cuisines : [];
            $this->cuisines = array_values(array_unique(array_merge($existingCuisines, $mappedCuisines)));
        }

        // Update specialties - merge with existing or set if empty
        $mappedSpecialties = $mapper->mapToSpecialties($placeData->types, $attributes);
        if (! empty($mappedSpecialties)) {
            $existingSpecialties = is_array($this->specialty) ? $this->specialty : [];
            $this->specialty = array_values(array_unique(array_merge($existingSpecialties, $mappedSpecialties)));
        }

        // Update images - download and upload Google Places photos to Digital Ocean
        if (! $skipPhotos && ! empty($placeData->photos)) {
            // Get raw images from database (not the processed accessor)
            $rawImages = $this->getRawOriginal('images');
            $existingImages = [];

            if ($rawImages) {
                $parsed = is_string($rawImages) ? json_decode($rawImages, true) : $rawImages;
                $existingImages = is_array($parsed) ? $parsed : [];
            }

            // If getRawOriginal doesn't work (e.g., in tests), try the attributes directly
            if (empty($existingImages) && isset($this->attributes['images'])) {
                $parsed = is_string($this->attributes['images'])
                    ? json_decode($this->attributes['images'], true)
                    : $this->attributes['images'];
                $existingImages = is_array($parsed) ? $parsed : [];
            }

            // Filter out photos we've already processed
            $newPhotoUrls = array_diff($placeData->photos, $existingGooglePhotoUrls);

            if (! empty($newPhotoUrls)) {
                // Upload only new Google Places photos to Digital Ocean
                $uploader = app(GooglePlacesImageUploader::class);
                $uploadedPaths = $uploader->uploadGooglePhotos($newPhotoUrls, $this->slug);

                if (! empty($uploadedPaths)) {
                    // Merge existing images with newly uploaded photos, avoiding duplicates
                    $allImages = array_unique(array_merge($existingImages, $uploadedPaths));

                    // Update the raw images attribute (bypass the accessor)
                    $this->attributes['images'] = json_encode(array_values($allImages));

                    // Update metadata to track which Google photo URLs we've processed
                    $updatedMetadata = $this->metadata->toArray();
                    $updatedMetadata['googlePhotoUrls'] = array_merge($existingGooglePhotoUrls, $newPhotoUrls);
                    $this->metadata = VenueMetadata::from($updatedMetadata);
                }
            }
        }

        return $this;
    }
}
