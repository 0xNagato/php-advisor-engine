<?php

namespace App\Models;

use App\Enums\EarningType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'percentage',
    ];

    /**
     * @return BelongsTo<User, Partner>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasManyThrough<Referral>
     */
    public function referrals(): HasManyThrough
    {
        return $this->hasManyThrough(
            Referral::class,
            User::class,
            'id',
            'referrer_id',
            'user_id',
            'id'
        );
    }

    /**
     * @return HasMany<Booking>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'partner_concierge_id')
            ->orWhere('partner_venue_id', $this->id)
            ->whereNotNull('confirmed_at');
    }

    /**
     * Get the total earnings for the partner, optionally filtered by a date range.
     */
    public function getTotalEarnings(Carbon|string|null $startDate = null, Carbon|string|null $endDate = null): int
    {
        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }

        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }

        $query = Booking::query()
            ->where(function (Builder $query): void {
                $query->where('partner_concierge_id', $this->id)
                    ->orWhere('partner_venue_id', $this->id);
            })
            ->whereNotNull('confirmed_at');

        if ($startDate instanceof Carbon) {
            $query->where('confirmed_at', '>=', $startDate->startOfDay());
        }

        if ($endDate instanceof Carbon) {
            $query->where('confirmed_at', '<=', $endDate->endOfDay());
        }

        return $query->withSum(['earnings' => function ($query) {
            $query->whereIn('type', [EarningType::PARTNER_CONCIERGE, EarningType::PARTNER_VENUE]);
        }], 'amount')
            ->get()
            ->sum(fn ($booking) =>
                // If partner is both concierge and venue, count full amount, otherwise half
                ($booking->partner_concierge_id == $this->id && $booking->partner_venue_id == $this->id)
                ? $booking->earnings_sum_amount
                : $booking->earnings_sum_amount / 2);
    }
}
