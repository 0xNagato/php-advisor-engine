<?php

namespace App\Models;

use App\Enums\VenueInvoiceStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VenueInvoice extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'venue_id',
        'created_by',
        'invoice_number',
        'start_date',
        'end_date',
        'prime_total',
        'non_prime_total',
        'total_amount',
        'currency',
        'due_date',
        'status',
        'pdf_path',
        'booking_ids',
        'sent_at',
        'paid_at',
    ];

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function bookings(): Collection
    {
        return Booking::query()->whereIn('id', $this->booking_ids)
            ->with(['earnings', 'schedule'])
            ->get();
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => VenueInvoiceStatus::SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsPending(): void
    {
        $this->update([
            'status' => VenueInvoiceStatus::PENDING,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => VenueInvoiceStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    public function markAsVoid(?string $reason = null): void
    {
        $this->update([
            'status' => VenueInvoiceStatus::VOID,
            'meta' => array_merge($this->meta ?? [], [
                'void_reason' => $reason,
                'voided_at' => now(),
            ]),
        ]);
    }

    public static function generateInvoiceNumber(Venue $venue): string
    {
        $prefix = config('app.env') === 'production' ? 'INV' : 'TEST';
        $venuePrefix = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $venue->name)), 0, 3);
        $venueId = str_pad($venue->id, 4, '0', STR_PAD_LEFT);
        $date = now()->format('Ymd');
        $sequence = static::query()->whereDate('created_at', now())->count() + 284;

        return "{$prefix}-{$venuePrefix}{$venueId}-{$date}-{$sequence}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'sent_at', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            VenueInvoiceStatus::VOID->value,
            VenueInvoiceStatus::PAID->value,
        ]);
    }

    public function scopeForDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate]);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            VenueInvoiceStatus::DRAFT->value,
            VenueInvoiceStatus::SENT->value,
            VenueInvoiceStatus::PENDING->value,
        ]);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'due_date' => 'datetime',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'status' => VenueInvoiceStatus::class,
            'booking_ids' => 'array',
            'prime_total' => 'decimal:2',
            'non_prime_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function name(): string
    {
        return Str::slug($this->invoice_number);
    }
}
