<?php

namespace App\Models;

use App\Data\SpecialRequest\SpecialRequestConversionData;
use App\Enums\SpecialRequestStatus;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;

/**
 * @property string $uuid
 */
class SpecialRequest extends Model
{
    use HasFactory;

    public const int PLATFORM_PERCENTAGE = 10;

    protected $fillable = [
        'venue_id',
        'concierge_id',
        'booking_id',
        'schedule_template_id',
        'booking_date',
        'booking_time',
        'party_size',
        'commission_requested_percentage',
        'minimum_spend',
        'special_request',
        'customer_first_name',
        'customer_last_name',
        'customer_phone',
        'customer_email',
        'status',
        'venue_message',
        'conversations',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'booking_time' => 'datetime',
            'status' => SpecialRequestStatus::class,
            'conversations' => DataCollection::class.':'.SpecialRequestConversionData::class,
            'meta' => AsArrayObject::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (SpecialRequest $specialRequest) {
            $specialRequest->uuid = Str::uuid();
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
     * @return BelongsTo<Concierge, $this>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<ScheduleTemplate, $this>
     */
    public function scheduleTemplate(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class);
    }

    protected function customerName(): Attribute
    {
        return Attribute::make(get: fn () => $this->customer_first_name.' '.$this->customer_last_name);
    }

    protected function venueTotalFee(): Attribute
    {
        return Attribute::make(get: function () {
            $commissionValue = ($this->commission_requested_percentage / 100) * $this->minimum_spend;
            $additionalFee = 0.07 * $commissionValue;

            return $commissionValue + $additionalFee;
        });
    }
}
