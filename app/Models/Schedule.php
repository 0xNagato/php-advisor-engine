<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'day_of_week',
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
        'is_bookable',
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

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_available', true)
            ->where('available_tables', '>', 0);
    }

    public function scopeUnavailable(Builder $query): Builder
    {
        return $query
            ->where('is_available', false)
            ->orWhere('available_tables', '<=', 0);
    }

    public function getFormattedStartTimeAttribute(): string
    {
        return date('g:i a', strtotime($this->attributes['start_time']));
    }

    public function getFormattedEndTimeAttribute(): string
    {
        return date('g:i a', strtotime($this->attributes['end_time']));
    }

    public function getIsBookableAttribute(): bool
    {
        return $this->is_available && $this->available_tables > 0;
    }
}
