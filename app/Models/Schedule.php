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
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    protected $appends = [
        'computed_available_tables',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getComputedAvailableTablesAttribute(): int
    {
        return $this->attributes['available_tables'] - $this->bookings()->count();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeAvailableAt(Builder $query, $time): Builder
    {
        $endOfDay = now()->endOfDay()->toTimeString();

        return $query->whereBetween('start_time', [$time, $endOfDay])
            ->where('is_available', true)
            ->where(function ($query) {
                $query->where('available_tables', '>', function ($query) {
                    $query->selectRaw('COUNT(*)')
                        ->from('bookings')
                        ->whereColumn('bookings.schedule_id', 'schedules.id');
                });
            });
    }
}
