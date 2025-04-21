<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperScheduleTemplate
 */
class ScheduleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'available_tables',
        'prime_time',
        'prime_time_fee',
        'party_size',
        'price_per_head',
        'minimum_spend_per_guest',
    ];

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(VenueTimeSlot::class);
    }

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'prime_time' => 'boolean',
        ];
    }

    /**
     * Find the schedule template for a specific venue, date/time, and party size.
     *
     * @param  Carbon  $dateTime  The date and time to find the template for.
     * @param  int  $partySize  The number of guests.
     */
    public static function findTemplateForDateTime(int $venueId, Carbon $dateTime, int $partySize): ?self
    {
        // Get lowercase day name (e.g., 'wednesday') consistent with DB storage
        $dayName = strtolower($dateTime->format('l'));
        $time = $dateTime->format('H:i:s'); // Time in HH:MM:SS format based on venue timezone

        // Find the template matching the exact party size
        return self::query() // Use query() for clarity
            ->where('venue_id', $venueId)
            ->where('day_of_week', $dayName) // Use the lowercase day name string
            ->where('is_available', true)
            ->where('start_time', '<=', $time) // Compare formatted time string
            ->where('end_time', '>=', $time)   // Compare formatted time string
            ->where('party_size', $partySize) // Find templates with this exact size
            ->first();
    }
}
