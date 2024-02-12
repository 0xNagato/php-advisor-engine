<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'time_slot_id',
        'concierge_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_count',
        'currency',
        'status',
        'total_fee',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Booking $booking) {
            $booking->total_fee = $booking->totalFee();
        });
    }

    public function totalFee(): int
    {
        $total_fee = 200;

        if ($this->guest_count > 2) {
            $total_fee += 50 * ($this->guest_count - 2);
        }

        return $total_fee * 100;
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
