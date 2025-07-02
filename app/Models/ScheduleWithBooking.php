<?php

namespace App\Models;

use App\Actions\Booking\CreateBooking;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $schedule_id
 * @property int $schedule_template_id
 * @property int $venue_id
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

    protected $with = ['venue'];

    public $timestamps = false;

    protected $appends = [
        'formatted_start_time',
        'no_wait',
        'is_within_buffer',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'booking_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function fee(int $partySize): int
    {
        if ($this->prime_time) {
            $extraPeople = max(0, $partySize - 2);

            if (! $this->relationLoaded('venue')) {
                $this->load('venue');
            }

            $extraFee = $extraPeople * $this->venue->increment_fee;
            $calculatedFee = ($this->effective_fee + $extraFee) * 100;

            // Cap the fee at 500 in any currency (50000 cents)
            return min($calculatedFee, CreateBooking::MAX_TOTAL_FEE_CENTS);
        }

        return 0;
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<VenueTimeSlot, $this>
     */
    public function timeSlots(): HasMany
    {
        return $this->hasMany(VenueTimeSlot::class, 'schedule_template_id');
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

    protected function noWait(): Attribute
    {
        return Attribute::make(get: fn () => $this->venue->no_wait ?? false);
    }

    protected function isWithinBuffer(): Attribute
    {
        return Attribute::make(
            get: function () {
                $venueTimezone = $this->venue->timezone;
                $now = now($venueTimezone);
                $bufferTime = $now->copy()->addMinutes(ReservationService::MINUTES_PAST);
                $bookingTime = new Carbon($this->booking_at, $venueTimezone);

                // Only check buffer if booking is for today
                if ($now->format('Y-m-d') !== $bookingTime->format('Y-m-d')) {
                    return false;
                }

                return ! $bookingTime->gte($bufferTime);
            }
        );
    }
}
