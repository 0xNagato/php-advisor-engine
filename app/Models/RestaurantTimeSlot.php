<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;
use Znck\Eloquent\Traits\BelongsToThrough as BelongsToThroughTrait;

/**
 * Class RestaurantTimeSlot
 * 
 * Represents a specific time slot for a restaurant. This is an override for a ScheduleTemplate.
 * If a RestaurantTimeSlot is found, it will be used by the ScheduleWithBookings view instead of the default ScheduleTemplate.
 *
 * @mixin IdeHelperRestaurantTimeSlot
 */
class RestaurantTimeSlot extends Model
{
    use BelongsToThroughTrait;
    use HasFactory;

    protected $fillable = [
        'schedule_template_id',
        'booking_date',
        'prime_time',
        'prime_time_fee',
    ];

    public function scheduleTemplate(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class);
    }

    public function restaurant(): BelongsToThrough
    {
        return $this->belongsToThrough(Restaurant::class, ScheduleTemplate::class);
    }
}
