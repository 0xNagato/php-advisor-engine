<?php

namespace App\Models;

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

        $query = Earning::query()
            ->join('bookings', 'earnings.booking_id', '=', 'bookings.id')
            ->where(function (Builder $query): void {
                $query->where('bookings.partner_concierge_id', $this->id)
                    ->orWhere('bookings.partner_venue_id', $this->id);
            })
            ->whereIn('earnings.type', ['partner_concierge', 'partner_venue'])
            ->whereNotNull('bookings.confirmed_at');

        if ($startDate instanceof Carbon) {
            $query->where('bookings.confirmed_at', '>=', $startDate->startOfDay());
        }

        if ($endDate instanceof Carbon) {
            $query->where('bookings.confirmed_at', '<=', $endDate->endOfDay());
        }

        return $query->sum('earnings.amount');
    }
}
