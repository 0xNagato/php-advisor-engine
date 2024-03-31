<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'percentage',
    ];

    protected $appends = [
        'last_months_earnings',
        'last_month_bookings',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // get total bookings for the partner in the last 30 days
    public function getLastMonthBookingsAttribute(): int
    {
        $startDate = now()->subDays(30);

        return Booking::where('partner_concierge_id', $this->id)
            ->orWhere('partner_restaurant_id', $this->id)
            ->where('created_at', '>=', $startDate)
            ->count();
    }

    public function getLastMonthsEarningsAttribute(): int
    {
        $startDate = now()->subDays(30);

        // Calculate the earnings for the concierge bookings
        $conciergeEarnings = Booking::where('partner_concierge_id', $this->id)
            ->where('created_at', '>=', $startDate)
            ->sum('partner_concierge_fee');

        // Calculate the earnings for the restaurant bookings
        $restaurantEarnings = Booking::where('partner_restaurant_id', $this->id)
            ->where('created_at', '>=', $startDate)
            ->sum('partner_restaurant_fee');

        // If the partner is a concierge
        if ($conciergeEarnings > 0 && $restaurantEarnings === 0) {
            return $conciergeEarnings;
        }

        // If the partner is a restaurant
        if ($restaurantEarnings > 0 && $conciergeEarnings === 0) {
            return $restaurantEarnings;
        }

        // If the partner is neither a concierge nor a restaurant
        return 0;
    }

    public function conciergeBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'partner_concierge_id');
    }

    public function restaurantBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'partner_restaurant_id');
    }

    public function scopeWithAllBookings($query)
    {
        return $query->with(['conciergeBookings', 'restaurantBookings']);
    }
}
