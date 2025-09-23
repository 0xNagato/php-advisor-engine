<?php

namespace App\Models;

use App\Data\AffiliateBrandingData;
use App\Enums\BookingStatus;
use App\Services\CurrencyConversionService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property AffiliateBrandingData|null $branding
 */
class VipCode extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'concierge_id', 'is_active', 'branding'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'branding' => AffiliateBrandingData::class,
        ];
    }

    public function link(): Attribute
    {
        return Attribute::make(get: fn () => route('v.booking', $this->code));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @return BelongsTo<Concierge, $this>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * @return HasMany<VenueCollection, $this>
     */
    public function venueCollections(): HasMany
    {
        return $this->hasMany(VenueCollection::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED]);
    }

    /**
     * @return HasManyThrough<Earning, Booking, $this>
     */
    public function earnings(): HasManyThrough
    {
        return $this->hasManyThrough(Earning::class, Booking::class)
            ->whereIn('earnings.type', ['concierge', 'concierge_bounty']);
    }

    public function totalEarningsGroupedByCurrency(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->earnings()
                ->selectRaw('SUM(earnings.amount) as total, earnings.currency')
                ->groupBy('earnings.currency', 'bookings.vip_code_id') // Add vip_code_id to the group by clause
                ->get()
                ->mapWithKeys(fn ($item) => [$item->currency => $item->total])
        );
    }

    public function totalEarningsInUSD(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currencyService = app(CurrencyConversionService::class);
                $earnings = $this->totalEarningsGroupedByCurrency;

                return $currencyService->convertToUSD($earnings->toArray());
            }
        );
    }

    /**
     * @return HasMany<VipSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(VipSession::class);
    }

    /**
     * Clean up expired sessions
     */
    public function cleanExpiredSessions(): void
    {
        $this->sessions()
            ->where('expires_at', '<', now())
            ->delete();
    }
}
