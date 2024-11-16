<?php

namespace App\Models\Traits;

use App\Models\Earning;
use App\Models\User;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait HasEarnings
{
    public function totalEarningsByCurrency(): Attribute
    {
        return Attribute::make(get: function () {
            $earnings = $this->earnings()->confirmed()->get(['amount', 'currency']);

            return $earnings->groupBy('currency')
                ->map(fn ($currencyGroup) => $currencyGroup->sum('amount') * 100)
                ->toArray();
        });
    }

    public function totalEarningsInUSD(): Attribute
    {
        return Attribute::make(get: function () {
            $currencyService = app(CurrencyConversionService::class);

            return $currencyService->convertToUSD($this->totalEarningsByCurrency);
        });
    }

    public function formattedTotalEarningsInUSD(): Attribute
    {
        return Attribute::make(get: fn () => money($this->totalEarningsInUSD, 'USD'));
    }

    /**
     * Description: HasManyThrough Relation with Earning
     *
     * @return HasManyThrough<Earning, User, $this>
     */
    public function earnings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Earning::class,
            User::class,
            'id', // Foreign key on the user's table
            'user_id', // Foreign key on the earning's table
            'user_id', // Local key on the venue's table
            'id'// Local key on the user's table...
        );
    }
}
