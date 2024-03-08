<?php

namespace App\Models;

use App\Enums\BookingStatus;
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
        'status',
        'partner_concierge_id',
        'partner_restaurant_id',
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

            if ($booking->concierge->user->partner_referral_id) {
                $booking->partner_concierge_id = $booking->concierge->user->partner_referral_id;
            }

            if ($booking->schedule->restaurant->user->partner_referral_id) {
                $booking->partner_restaurant_id = $booking->schedule->restaurant->user->partner_referral_id;
            }

            $payouts = $booking->calculatePayouts();
            $booking->restaurant_earnings = $payouts['restaurant'];
            $booking->concierge_earnings = $payouts['concierge'];
            $booking->charity_earnings = $payouts['charity'];
            $booking->platform_earnings = $payouts['platform'];
            $booking->partner_concierge_fee = $payouts['partner_concierge'];
            $booking->partner_restaurant_fee = $payouts['partner_restaurant'];
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
     * Calculate the payouts for the booking.
     *
     * This method calculates the payouts for the restaurant, platform, concierge, charity, and partners.
     * The total fee is divided among these entities based on predefined percentages.
     * The concierge's share is calculated as 25% of the platform's share.
     * The charity's share is calculated as 5% of each party's share.
     * If a concierge or restaurant has a partner, the partner's fee is calculated as a percentage of the platform's share.
     * If there is more than one partner, the partner fees are split proportionally based on their percentages.
     * The method throws a RuntimeException if the sum of all payouts does not equal the total fee.
     *
     * @return array{
     *     restaurant: int,
     *     platform: int,
     *     concierge: int,
     *     charity: int,
     *     partner_concierge: int,
     *     partner_restaurant: int
     * } An associative array where the keys are the entities and the values are the payouts in cents.
     *
     * @throws RuntimeException If the sum of all payouts does not equal the total fee.
     */
    public function calculatePayouts(): array
    {
        $totalFee = $this->total_fee;

        $restaurantPercentage = $this->schedule->restaurant->payout_restaurant / 100;
        $platformPercentage = 1 - $restaurantPercentage;
        $conciergePercentage = 0.25;

        $restaurantPayout = (int)($totalFee * $restaurantPercentage);
        $platformPayout = (int)($totalFee * $platformPercentage);

        // Calculate the concierge's share and subtract it from the platform's share.
        $conciergePayout = (int)($platformPayout * $conciergePercentage); // Concierge gets 25% of platform's share
        $platformPayout -= $conciergePayout;

        // Define the charity percentages for each party
        $restaurantCharityPercentage = $this->schedule->restaurant->user->charity_percentage / 100;
        $conciergeCharityPercentage = $this->concierge->user->charity_percentage / 100;
        $platformCharityPercentage = 0.05;

        // Calculate the charity's share from each party
        $restaurantCharityPayout = (int)($restaurantPayout * $restaurantCharityPercentage);  // 5% of restaurant's share
        $platformCharityPayout = (int)($platformPayout * $platformCharityPercentage);  // 5% of platform's share
        $conciergeCharityPayout = (int)($conciergePayout * $conciergeCharityPercentage);  // 5% of concierge's share

        // Subtract the charity's share from the restaurant's, platform's, and concierge's shares
        $restaurantPayout -= $restaurantCharityPayout;
        $platformPayout -= $platformCharityPayout;
        $conciergePayout -= $conciergeCharityPayout;

        $partnerConciergeFee = 0;
        $partnerRestaurantFee = 0;

        if ($this->partnerConcierge && $this->partnerConcierge->partner) {
            $partnerConciergeFee = (int)($platformPayout * ($this->partnerConcierge->partner->percentage / 100));
        }

        if ($this->partnerRestaurant && $this->partnerRestaurant->partner) {
            $partnerRestaurantFee = (int)($totalFee * ($this->partnerRestaurant->partner->percentage / 100));
        }

        // Subtract the partner fees from the platform fee
        $platformPayout -= $partnerConciergeFee;
        $platformPayout -= $partnerRestaurantFee;

        // If there is more than one partner, split the earnings proportionally based on their percentages
        if ($partnerConciergeFee > 0 && $partnerRestaurantFee > 0) {
            $totalPartnerFee = $partnerConciergeFee + $partnerRestaurantFee;

            $totalPercentage = 0;

            if ($this->partnerConcierge && $this->partnerConcierge->partner) {
                $totalPercentage += $this->partnerConcierge->partner->percentage;
            }

            if ($this->partnerRestaurant && $this->partnerRestaurant->partner) {
                $totalPercentage += $this->partnerRestaurant->partner->percentage;
            }

            if ($this->partnerConcierge && $this->partnerConcierge->partner && $this->partnerRestaurant && $this->partnerRestaurant->partner) {
                $totalPercentage = $this->partnerConcierge->partner->percentage + $this->partnerRestaurant->partner->percentage;
                $partnerConciergeFee = (int)($totalPartnerFee * ($this->partnerConcierge->partner->percentage / $totalPercentage));
                $partnerRestaurantFee = $totalPartnerFee - $partnerConciergeFee;
            }
        }
        // Calculate the total charity's share
        $charityPayout = $restaurantCharityPayout + $platformCharityPayout + $conciergeCharityPayout;

        $payouts = [
            'restaurant' => $restaurantPayout,
            'platform' => $platformPayout,
            'concierge' => $conciergePayout,
            'charity' => $charityPayout,
            'partner_concierge' => $partnerConciergeFee,
            'partner_restaurant' => $partnerRestaurantFee,
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

    public function partnerConcierge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_concierge_id');
    }

    public function partnerRestaurant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_restaurant_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function getGuestNameAttribute(): string
    {
        return $this->guest_first_name . ' ' . $this->guest_last_name;
    }

    // In Booking.php

// In Booking.php

    public function getPartnerEarningsAttribute()
    {
        $earnings = 0;

        if ($this->partnerConcierge && $this->concierge->user->partner_referral_id === $this->partnerConcierge->id) {
            $earnings += $this->partner_concierge_fee;
        }

        if ($this->partnerRestaurant && $this->schedule->restaurant->user->partner_referral_id === $this->partnerRestaurant->id) {
            $earnings += $this->partner_restaurant_fee;
        }

        return $earnings;
    }
}
