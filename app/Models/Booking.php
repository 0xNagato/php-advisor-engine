<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use RuntimeException;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'schedule_id',
        'concierge_id',
        'guest_first_name',
        'guest_last_name',
        'guest_email',
        'guest_phone',
        'guest_count',
        'currency',
        'total_fee',
        'booking_at',
        'stripe_charge',
        'stripe_charge_id',
    ];

    protected $appends = [
        'guest_name',
    ];

    protected $casts = [
        'booking_at' => 'datetime',
        'status' => BookingStatus::class,
        'stripe_charge' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Booking $booking) {
            $booking->uuid = Str::uuid();
        });

        static::saving(function (Booking $booking) {
            $booking->total_fee = $booking->totalFee();

            $payouts = $booking->calculatePayouts();
            $booking->restaurant_earnings = $payouts['restaurant'];
            $booking->concierge_earnings = $payouts['concierge'];
            $booking->charity_earnings = $payouts['charity'];
            $booking->platform_earnings = $payouts['platform'];
        });
    }

    public function totalFee(): int
    {
        $total_fee = $this->schedule->restaurant->booking_fee;

        if ($this->guest_count > 2) {
            $total_fee += 50 * ($this->guest_count - 2);
        }

        return $total_fee * 100;
    }

    /**
     * Calculates the payouts for the restaurant, platform, concierge and charity.
     *
     * The total fee of the booking is divided among the restaurant, platform and concierge.
     * The restaurant gets 60% of the total fee, and the platform gets 40%.
     * The concierge's share is calculated as 25% of the platform's share.
     *
     * A charity percentage is defined for each party (restaurant, platform and concierge), which is currently set to 5%.
     * The charity's share from each party is calculated as this percentage of the party's share.
     * This charity amount is then subtracted from the respective party's share.
     *
     * The total charity's share is the sum of the charity amounts from each party.
     *
     * The method returns an associative array with the final calculated payouts for the restaurant, platform, concierge, and charity.
     *
     * If the sum of all payouts does not equal the total fee, an exception is thrown.
     *
     * @return array<string, int> The payouts for the restaurant, platform, concierge and charity.
     *
     * @throws Exception If the sum of all payouts does not equal the total fee.
     */
    public function calculatePayouts(): array
    {
        $totalFee = $this->total_fee;

        $restaurantPercentage = $this->schedule->restaurant->payout_restaurant / 100;
        $platformPercentage = 1 - $restaurantPercentage;
        $conciergePercentage = 0.25;

        $restaurantPayout = (int) ($totalFee * $restaurantPercentage);
        $platformPayout = (int) ($totalFee * $platformPercentage);

        // Calculate the concierge's share and subtract it from the platform's share.
        $conciergePayout = (int) ($platformPayout * $conciergePercentage); // Concierge gets 25% of platform's share
        $platformPayout -= $conciergePayout;

        // Define the charity percentages for each party
        $restaurantCharityPercentage = $this->schedule->restaurant->user->charity_percentage / 100;
        $conciergeCharityPercentage = $this->concierge->user->charity_percentage / 100;
        $platformCharityPercentage = 0.05;

        // Calculate the charity's share from each party
        $restaurantCharityPayout = (int) ($restaurantPayout * $restaurantCharityPercentage);  // 5% of restaurant's share
        $platformCharityPayout = (int) ($platformPayout * $platformCharityPercentage);  // 5% of platform's share
        $conciergeCharityPayout = (int) ($conciergePayout * $conciergeCharityPercentage);  // 5% of concierge's share

        // Subtract the charity's share from the restaurant's, platform's, and concierge's shares
        $restaurantPayout -= $restaurantCharityPayout;
        $platformPayout -= $platformCharityPayout;
        $conciergePayout -= $conciergeCharityPayout;

        // Calculate the total charity's share
        $charityPayout = $restaurantCharityPayout + $platformCharityPayout + $conciergeCharityPayout;

        $payouts = [
            'restaurant' => $restaurantPayout,
            'platform' => $platformPayout,
            'concierge' => $conciergePayout,
            'charity' => $charityPayout,
        ];

        // Check if the sum of all payouts equals the total fee
        $totalPayouts = array_sum($payouts);
        if ($totalPayouts !== $totalFee) {
            throw new RuntimeException('The sum of all payouts does not equal the total fee.');
        }

        return $payouts;
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', BookingStatus::CONFIRMED);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function getGuestNameAttribute(): string
    {
        return $this->guest_first_name.' '.$this->guest_last_name;
    }
}
