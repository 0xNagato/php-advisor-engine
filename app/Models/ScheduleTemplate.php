<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleTemplate extends Model
{
    protected $fillable = [
        'restaurant_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'available_tables',
        'prime_time',
        'prime_time_fee',
        'party_size',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'prime_time' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updated(function (ScheduleTemplate $scheduleTemplate) {
            Schedule::where('restaurant_id', $scheduleTemplate->restaurant_id)
                ->where('start_time', $scheduleTemplate->getOriginal('start_time'))
                ->where('end_time', $scheduleTemplate->getOriginal('end_time'))
                ->where('day_of_week', $scheduleTemplate->getOriginal('day_of_week'))
                ->where('party_size', $scheduleTemplate->getOriginal('party_size'))
                ->where('booking_date', '>=', now()->format('Y-m-d'))
                ->update([
                    'start_time' => $scheduleTemplate->start_time,
                    'end_time' => $scheduleTemplate->end_time,
                    'is_available' => $scheduleTemplate->is_available,
                    'available_tables' => $scheduleTemplate->available_tables,
                    'prime_time' => $scheduleTemplate->prime_time,
                    'prime_time_fee' => $scheduleTemplate->prime_time_fee,
                    'party_size' => $scheduleTemplate->party_size,
                ]);
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
