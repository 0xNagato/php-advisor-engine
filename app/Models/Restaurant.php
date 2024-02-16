<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'open_days' => 'array',
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

    /**
     * Scope a query to only include restaurants that are available at a specific time.
     *
     * @param  mixed  $time
     */
    public function scopeAvailableAt(Builder $query, string $time): Builder
    {
        $dayOfWeek = strtolower($time->format('l')); // Get the day of the week in lowercase

        return $query->whereHas('schedules', function ($query) use ($time) {
            $query->availableAt($time);
        })->where("days_open->$dayOfWeek", 'open') // Check if the restaurant is open on that day
            ->with(['schedules' => function ($query) use ($time) {
                $query->availableAt($time);
            }]);
    }
}
