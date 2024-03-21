<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'start_time',
        'end_time',
        'is_available',
        'available_tables',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'start_time',
        'end_time',
    ];

    protected $appends = [
        'computed_available_tables',
        'formatted_start_time',
        'formatted_end_time',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getComputedAvailableTablesAttribute(): int
    {
        $bookingsTodayCount = $this->bookings()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return $this->attributes['available_tables'] - $bookingsTodayCount;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getFormattedStartTimeAttribute(): string
    {
        return date('g:i a', strtotime($this->attributes['start_time']));
    }

    public function getFormattedEndTimeAttribute(): string
    {
        return date('g:i a', strtotime($this->attributes['end_time']));
    }
}
