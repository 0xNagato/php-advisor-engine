<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperBookingCustomerReminderLog
 */
class BookingCustomerReminderLog extends Model
{
    use HasFactory;

    protected $table = 'booking_customer_reminder_logs';

    protected $fillable = [
        'booking_id',
        'guest_phone',
        'sent_at',
    ];

    public function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
