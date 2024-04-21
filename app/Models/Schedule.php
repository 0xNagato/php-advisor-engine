<?php

namespace App\Models;

use Carbon\Carbon;
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
        'prime_time',
        'prime_time_fee',
        'party_size',
        'booking_date',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'start_time',
        'end_time',
        'prime_time' => 'boolean',
        'booking_date' => 'date',
        'booking_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_start_time',
        'formatted_end_time',
        'is_bookable',
        'booking_at',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getBookingAtAttribute(): string
    {
        $datePart = $this->booking_date->toDateString();

        return Carbon::parse($datePart . ' ' . $this->start_time)->toDateTimeString();
    }

    public function fee(int $partySize): int
    {
        if ($this->prime_time) {
            return 0;
        }

        $extraPeople = max(0, $partySize - 2);
        $extraFee = $extraPeople * 50;

        $specialPrice = $this->restaurant->specialPricing()->where('date', $this->booking_date)->first();
        $fee = $specialPrice->fee ?? $this->restaurant->booking_fee;

        return ($fee + $extraFee) * 100;
    }

    public function getConfirmedBookingsCountAttribute(): int
    {
        return $this->bookings()->where('status', 'confirmed')->count();
    }

    public function getRemainingTablesAttribute(): int
    {
        return $this->available_tables - $this->confirmed_bookings_count;
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
        return $this->is_available && $this->remaining_tables > 0;
    }
}
