<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Services\CurrencyConversionService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @mixin IdeHelperVipCode
 */
class VipCode extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['code', 'concierge_id'];

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getAuthPassword(): string
    {
        return $this->code;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function link(): Attribute
    {
        return Attribute::make(get: fn () => route('vip.login').'/'.$this->code);
    }

    /**
     * @return BelongsTo<Concierge, VipCode>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * @return HasMany<Booking>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('status', BookingStatus::CONFIRMED);
    }

    /**
     * @return HasManyThrough<Earning>
     */
    public function earnings(): HasManyThrough
    {
        return $this->hasManyThrough(Earning::class, Booking::class)
            ->whereHas('booking', function (Builder $query) {
                $query->where('status', BookingStatus::CONFIRMED);
            })
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

    public function confirmedBookingsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bookings()->count()
        );
    }
}
