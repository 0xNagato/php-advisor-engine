<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $schedule_id
 * @property int $schedule_template_id
 * @property int $restaurant_id
 * @property string $schedule_start
 * @property string $schedule_end
 * @property int $is_available
 * @property bool $is_bookable
 * @property int $remaining_tables
 * @property int $effective_fee
 * @property bool $prime_time
 * @property int $party_size
 * @property string $booking_date
 * @property string $booking_at
 * @property string $start_time
 * @property string $end_time
 *
 * @mixin IdeHelperScheduleWithBooking
 */
class ScheduleWithBooking extends Model
{
    protected $table = 'schedule_with_bookings';

    public $timestamps = false;

    /**
     * @return BelongsTo<Restaurant, ScheduleWithBooking>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function fee(int $partySize): int
    {
        if ($this->prime_time) {
            $extraPeople = max(0, $partySize - 2);

            if (! $this->relationLoaded('restaurant')) {
                $this->load('restaurant');
            }

            $extraFee = $extraPeople * $this->restaurant->increment_fee;

            return ($this->effective_fee + $extraFee) * 100;
        }

        return 0;
    }

    /**
     * @return HasMany<Booking>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    protected function bookingAt(): Attribute
    {
        return Attribute::make(get: fn () => $this->schedule_start);
    }

    protected function formattedStartTime(): Attribute
    {
        return Attribute::make(get: fn () => date('g:ia', strtotime($this->start_time)));
    }

    protected function formattedEndTime(): Attribute
    {
        return Attribute::make(get: fn () => date('g:ia', strtotime($this->end_time)));
    }

    protected function isBookable(): Attribute
    {
        return Attribute::make(get: fn () => $this->is_available && $this->remaining_tables > 0);
    }

    protected function hasLowInventory(): Attribute
    {
        return Attribute::make(get: fn () => $this->is_bookable && $this->remaining_tables <= 5);
    }

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'booking_at' => 'datetime',
        ];
    }
}
