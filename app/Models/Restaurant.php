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
    ];

    protected $casts = [
        'open_days' => 'array',
        'contacts' => DataCollection::class . ':' . RestaurantContactData::class,
        'non_prime_time' => 'array',
        'business_hours' => 'array',
    ];

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
        });

        static::created(function (Restaurant $restaurant) {
            $restaurant->createDefaultSchedules();
        });
    }

    public function createDefaultSchedules(): void
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOfWeek as $day) {
            $startTime = Carbon::createFromTime(0, 0); // Start of the day
            $endTime = Carbon::createFromTime(23, 59); // End of the day

            while ($startTime->lessThanOrEqualTo($endTime)) {
                $isAvailable = $startTime->hour >= 12 && $startTime->hour < 22; // Available from 12pm to 10pm

                $this->schedules()->create([
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $startTime->addMinutes(30)->format('H:i:s'),
                    'is_available' => $isAvailable,
                    'available_tables' => $isAvailable ? 10 : 0, // Set available tables to 10 if available, 0 otherwise
                    'day_of_week' => $day,
                ]);
            }
        }
    }

    /**
     * Get the schedules for the restaurant.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
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
        $currentDay = strtolower(now()->format('l')); // Get the current day of the week in lowercase

        return $query->where("open_days->{$currentDay}", 'open'); // Check if the restaurant is open on that day
    }

    public function scopeOpenOnDate(Builder $query, string $date): Builder
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l')); // Convert the date to a day of the week

        return $query->where("open_days->{$dayOfWeek}", 'open'); // Check if the restaurant is open on that day
    }

    public function getPriceForDate(string $date): float
    {
        // Parse the date
        $date = Carbon::parse($date)->format('Y-m-d');

        // Check if there's a special price for the given date
        $specialPrice = $this->specialPricing()->where('date', $date)->first();

        return $specialPrice->fee ?? $this->booking_fee;
    }

    public function specialPricing(): HasMany
    {
        return $this->hasMany(SpecialPricingRestaurant::class);
    }
}
