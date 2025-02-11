<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as BelongsToThroughTrait;

/**
 * Class VenueTimeSlot
 *
 * Represents a specific time slot for a venue. This is an override for a ScheduleTemplate.
 * If a VenueTimeSlot is found, it will be used by the ScheduleWithBookings view instead of the default ScheduleTemplate.
 */
class VenueTimeSlot extends Model
{
    use BelongsToThroughTrait;
    use HasFactory;

    protected $fillable = [
        'schedule_template_id',
        'booking_date',
        'prime_time',
        'prime_time_fee',
        'is_available',
        'available_tables',
        'price_per_head',
        'minimum_spend_per_guest',
    ];

    /**
     * @return BelongsTo<ScheduleTemplate, $this>
     */
    public function scheduleTemplate(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class);
    }

    public function venue(): BelongsToThrough
    {
        return $this->belongsToThrough(Venue::class, ScheduleTemplate::class);
    }

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'prime_time' => 'boolean',
            'prime_time_fee' => 'integer',
            'price_per_head' => 'integer',
            'minimum_spend_per_guest' => 'integer',
            'available_tables' => 'integer',
            'booking_date' => 'date',
        ];
    }
}
