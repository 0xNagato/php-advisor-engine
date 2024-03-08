<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return Booking::where('partner_concierge_id', $this->id)
            ->orWhere('partner_restaurant_id', $this->id)
            ->where('created_at', '>=', $startDate)
            ->sum('partner_concierge_fee', 'partner_restaurant_fee');
    }
}
