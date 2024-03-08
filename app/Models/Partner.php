<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function user()
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
        $totalEarnings = Booking::where('partner_concierge_id', $this->id)
            ->orWhere('partner_restaurant_id', $this->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('SUM(partner_concierge_fee) + SUM(partner_restaurant_fee) as total')
            ->first()
            ->total;

        return $totalEarnings ?? 0;
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
