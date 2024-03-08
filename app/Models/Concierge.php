<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Concierge extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'hotel_name',
    ];

    protected $appends = [
        'payout_percentage',
        'sales'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate the payout percentage based on the amount sales.
     *
     * @return int The payout percentage.
     */
    public function getPayoutPercentageAttribute(): int
    {
        $sales = $this->sales;

        if ($sales >= 0 && $sales <= 10) {
            return 10;
        }

        if ($sales >= 11 && $sales <= 20) {
            return 12;
        }

        return 15;
    }

    /**
     * Get the amount confirmed bookings.
     *
     * @return int The amount confirmed bookings.
     */
    public function getSalesAttribute(): int
    {
        return $this->bookings()->where('status', BookingStatus::CONFIRMED)->count();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'concierge_id');
    }
}
