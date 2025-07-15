<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

/**
 * @mixin IdeHelperBookingModificationRequest
 */
class BookingModificationRequest extends Model
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'booking_id',
        'requested_by_id',
        'original_guest_count',
        'requested_guest_count',
        'original_time',
        'requested_time',
        'original_booking_at',
        'request_booking_at',
        'original_schedule_template_id',
        'requested_schedule_template_id',
        'status',
        'rejection_reason',
        'responded_at',
        'meta',
    ];

    // Status constants
    const string STATUS_PENDING = 'pending';

    const string STATUS_APPROVED = 'approved';

    const string STATUS_REJECTED = 'rejected';

    const string STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'responded_at' => 'datetime',
            'original_booking_at' => 'date',
            'request_booking_at' => 'date',
        ];
    }

    // Relationships

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    /**
     * @return BelongsTo<ScheduleTemplate, $this>
     */
    public function originalSchedule(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class, 'original_schedule_template_id');
    }

    /**
     * @return BelongsTo<ScheduleTemplate, $this>
     */
    public function requestedSchedule(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class, 'requested_schedule_template_id');
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function hasTimeChange(): bool
    {
        return $this->original_time !== $this->requested_time;
    }

    public function hasGuestCountChange(): bool
    {
        return $this->original_guest_count !== $this->requested_guest_count;
    }

    protected function requestSource(): Attribute
    {
        return Attribute::make(get: fn () => $this->meta['request_source'] ?? 'unknown');
    }

    public function wasRequestedByCustomer(): bool
    {
        return $this->request_source === 'customer';
    }

    public function wasRequestedByConcierge(): bool
    {
        return $this->request_source === 'concierge';
    }

    public function markAsApproved(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'responded_at' => now(),
        ]);
    }

    public function markAsRejected(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'responded_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'responded_at' => now(),
        ]);
    }

    protected function formattedOriginalTime(): Attribute
    {
        return Attribute::make(get: fn () => date('g:i A', strtotime($this->original_time)));
    }

    protected function formattedRequestedTime(): Attribute
    {
        return Attribute::make(get: fn () => date('g:i A', strtotime($this->requested_time)));
    }
}
