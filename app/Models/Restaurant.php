<?php

namespace App\Models;

use App\Data\RestaurantContactData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;

/**
 * Class Restaurant
 *
 * @mixin IdeHelperRestaurant
 */
class Restaurant extends Model
{
    use HasFactory;

    public const int DEFAULT_TABLES = 10;

    public const int DEFAULT_START_HOUR = 11; // 11:00 AM

    public const int DEFAULT_END_HOUR = 22; // 10:00 PM

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'restaurant_name',
        'slug',
        'contact_phone',
        'payout_restaurant',
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
        'restaurant_logo_path',
        'region',
        'increment_fee',
        'non_prime_fee_per_head',
        'non_prime_type',
    ];

    protected $casts = [
        'open_days' => 'array',
        'contacts' => DataCollection::class.':'.RestaurantContactData::class,
        'non_prime_time' => 'array',
        'business_hours' => 'array',
        'party_sizes' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Restaurant $restaurant) {
            $restaurant->slug = Str::slug("$restaurant->region-$restaurant->restaurant_name");

            $restaurant->open_days = [
                'monday' => 'open',
                'tuesday' => 'open',
                'wednesday' => 'open',
                'thursday' => 'open',
                'friday' => 'open',
                'saturday' => 'open',
                'sunday' => 'open',
            ];

            $restaurant->party_sizes = [
                'Special Request' => 0,
                '2' => 2,
                '4' => 4,
                '6' => 6,
                '8' => 8,
            ];
        });

        static::created(static function (Restaurant $restaurant) {
            $restaurant->createDefaultSchedules();
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
                        'restaurant_id' => $this->id,
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

    public function scheduleTemplates(): HasMany
    {
        return $this->hasMany(ScheduleTemplate::class);
    }

    public function timeSlots(): HasManyThrough
    {
        return $this->hasManyThrough(RestaurantTimeSlot::class, ScheduleTemplate::class);
    }

    public function getLogoAttribute(): ?string
    {
        return $this->restaurant_logo_path ? Storage::url($this->restaurant_logo_path) : null;
    }

    /**
     * Get the schedules for the restaurant.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ScheduleWithBooking::class);
    }

    /**
     * Get the user that owns the restaurant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region', 'id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereHas('user', function (Builder $query) {
            $query->whereNotNull('secured_at');
        })->where('is_suspended', false);
    }

    public function specialPricing(): HasMany
    {
        return $this->hasMany(SpecialPricingRestaurant::class);
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
}
