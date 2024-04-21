<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AvailableSchedule extends Schedule
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'available_schedules';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the bookings for the available schedule.
     *
     * @return HasMany
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'schedule_id');
    }
}
