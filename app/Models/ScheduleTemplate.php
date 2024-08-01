<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperScheduleTemplate
 */
class ScheduleTemplate extends Model
{
    use HasFactory;

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

    /**
     * @return BelongsTo<Restaurant, ScheduleTemplate>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'prime_time' => 'boolean',
        ];
    }
}
