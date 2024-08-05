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
 *
 * @mixin IdeHelperVenueTimeSlot
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
    ];

    /**
     * @return BelongsTo<ScheduleTemplate, VenueTimeSlot>
     */
    public function scheduleTemplate(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class);
    }

    public function venue(): BelongsToThrough
    {
        return $this->belongsToThrough(Venue::class, ScheduleTemplate::class);
    }
}
