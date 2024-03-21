<?php

namespace App\Models;

use App\Data\RestaurantContactData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    protected $casts = [
        'open_days' => 'array',
        'contacts' => DataCollection::class.':'.RestaurantContactData::class,
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
    }

    /**
     * Get the user that owns the restaurant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the schedules for the restaurant.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
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
}
