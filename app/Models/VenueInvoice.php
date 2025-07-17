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

/**
 */
class VenueInvoice extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'venue_id',
        'venue_group_id',
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
        'stripe_invoice_id',
        'stripe_invoice_url',
        'booking_ids',
        'venues_data',
        'sent_at',
        'paid_at',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (VenueInvoice $invoice) {
            $invoice->update([
                'invoice_number' => static::generateInvoiceNumber($invoice),
            ]);
        });
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * @return BelongsTo<VenueGroup, $this>
     */
    public function venueGroup(): BelongsTo
    {
        return $this->belongsTo(VenueGroup::class);
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

    /**
     * Generate an invoice number with venue group support
     */
    public static function generateInvoiceNumber(VenueInvoice $invoice): string
    {
        $prefix = config('app.env') === 'production' ? 'invoice' : 'invoice-test';
        $venuePrefix = $invoice->venue_group_id
            ? Str::slug($invoice->venueGroup->name)
            : Str::slug($invoice->venue->name);
        $id = $invoice->id;

        return "{$prefix}-{$venuePrefix}-{$id}";
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
            'venues_data' => 'array',
            'prime_total' => 'decimal:2',
            'non_prime_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function name(): string
    {
        return Str::slug($this->invoice_number);
    }

    /**
     * Get the Stripe invoice URL if available
     */
    public function getStripeInvoiceUrl(): ?string
    {
        return $this->stripe_invoice_url;
    }
}
