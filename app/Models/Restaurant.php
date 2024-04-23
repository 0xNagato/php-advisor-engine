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
use Spatie\LaravelData\DataCollection;

/**
 * Class Restaurant
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
    ];

    protected $casts = [
        'open_days' => 'array',
        'contacts' => DataCollection::class.':'.RestaurantContactData::class,
        'non_prime_time' => 'array',
        'business_hours' => 'array',
        'party_sizes' => 'array',
    ];

    /** @noinspection PackedHashtableOptimizationInspection */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Restaurant $restaurant) {
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
                '2' => 2,
                '4' => 4,
                '6' => 6,
                '8' => 8,
            ];
        });

        static::created(function (Restaurant $restaurant) {
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
                        'prime_time' => (bool) $isAvailable,
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

    public function generatePreviousMonthSchedules(): void
    {
        // Get the start and end dates of the previous month
        $start = now()->subMonth()->startOfMonth();
        $end = now()->subMonth()->endOfMonth();

        // Loop through each day of the previous month
        for ($date = $start; $date->lte($end); $date->addDay()) {
            // Get the schedule templates for that day of the week
            $dayOfWeek = strtolower($date->format('l'));
            $scheduleTemplates = $this->scheduleTemplates()->where('day_of_week', $dayOfWeek)->get();

            // For each schedule template, create a new schedule
            foreach ($scheduleTemplates as $scheduleTemplate) {
                Schedule::create([
                    'restaurant_id' => $this->id,
                    'start_time' => $scheduleTemplate->start_time,
                    'end_time' => $scheduleTemplate->end_time,
                    'is_available' => $scheduleTemplate->is_available,
                    'available_tables' => $scheduleTemplate->available_tables,
                    'day_of_week' => $dayOfWeek,
                    'party_size' => $scheduleTemplate->party_size,
                    'booking_date' => $date->format('Y-m-d'),
                    'prime_time' => $scheduleTemplate->prime_time,
                    'prime_time_fee' => $scheduleTemplate->prime_time_fee,
                ]);
            }
        }
    }

    public function scheduleTemplates(): HasMany
    {
        return $this->hasMany(ScheduleTemplate::class);
    }

    /**
     * Get the schedules for the restaurant.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function generateScheduleForDate(Carbon $date): void
    {
        $dayOfWeek = strtolower($date->format('l'));
        $scheduleTemplates = $this->scheduleTemplates()->where('day_of_week', $dayOfWeek)->get();
        $schedulesData = [];

        foreach ($scheduleTemplates as $scheduleTemplate) {
            $schedulesData[] = [
                'restaurant_id' => $this->id,
                'start_time' => $scheduleTemplate->start_time,
                'end_time' => $scheduleTemplate->end_time,
                'is_available' => $scheduleTemplate->is_available,
                'available_tables' => $scheduleTemplate->available_tables,
                'day_of_week' => $dayOfWeek,
                'party_size' => $scheduleTemplate->party_size,
                'booking_date' => $date->format('Y-m-d'),
                'prime_time' => $scheduleTemplate->prime_time,
                'prime_time_fee' => $scheduleTemplate->prime_time_fee,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($schedulesData)) {
            Schedule::insert($schedulesData);
        }
    }

    /**
     * Get the user that owns the restaurant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(Booking::class, Schedule::class);
    }

    public function availableSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class)->where('is_available', true);
    }

    public function unavailableSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class)->where('is_available', false);
    }

    public function scopeOpenToday(Builder $query): Builder
    {
        $currentDay = strtolower(now()->format('l'));

        return $query->where("open_days->$currentDay", 'open');

        // ->whereHas('user', function (Builder $query) {
        //         $query->whereNotNull('secured_at');
        //     })
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereHas('user', function (Builder $query) {
            $query->whereNotNull('secured_at');
        });
    }

    public function scopeOpenOnDate(Builder $query, string $date): Builder
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

        return $query->where("open_days->$dayOfWeek", 'open');
    }

    public function getPriceForDate(string $date): float
    {
        // Parse the date
        $date = Carbon::parse($date)->format('Y-m-d');

        // Check if there's a special price for the given date.
        $specialPrice = $this->specialPricing()->where('date', $date)->first();

        return $specialPrice->fee ?? $this->booking_fee;
    }

    public function specialPricing(): HasMany
    {
        return $this->hasMany(SpecialPricingRestaurant::class);
    }
}
