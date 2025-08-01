<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\EarningType;
use App\Models\Traits\HasEarnings;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Concierge extends Model
{
    use HasEarnings, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'hotel_name',
        'allowed_venue_ids',
        'venue_group_id',
        'is_qr_concierge',
        'revenue_percentage',
        'can_override_duplicate_checks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_venue_ids' => 'array',
            'can_override_duplicate_checks' => 'boolean',
        ];
    }

    /**
     * Ensure allowed_venue_ids are always cast to integers.
     *
     * @return Attribute<array<int, int>, array<int, int|string>>
     */
    protected function allowedVenueIds(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $ids = json_decode($value, true) ?? [];

                return array_map('intval', $ids);
            },
            set: function ($value) {
                if (is_string($value)) {
                    $value = json_decode($value, true) ?? [];
                }

                return json_encode(array_map('intval', $value));
            }
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate the payout percentage based on the amount sales.
     */
    protected function payoutPercentage(): Attribute
    {
        return Attribute::make(get: function () {
            $sales = $this->sales_this_month;
            if ($sales >= 0 && $sales <= 10) {
                return 10;
            }
            if ($sales >= 11 && $sales <= 20) {
                return 12;
            }

            return 15;
        });
    }

    /**
     * Get the amount confirmed bookings.
     */
    protected function sales(): Attribute
    {
        return Attribute::make(get: fn () => $this->bookings()->confirmed()->count());
    }

    /**
     * Get the amount confirmed bookings.
     */
    protected function salesThisMonth(): Attribute
    {
        return Attribute::make(get: fn () => $this->bookings()
            ->confirmed()
            ->whereMonth('created_at', now()->month)
            ->count());
    }

    public function referralEarningsByCurrency(): Attribute
    {
        return Attribute::make(get: function () {
            $earnings = $this->earnings()->confirmed()
                ->whereIn('type', [
                    EarningType::CONCIERGE_REFERRAL_1,
                    EarningType::CONCIERGE_REFERRAL_2,
                ])
                ->get(['amount', 'currency']);

            return $earnings->groupBy('currency')
                ->map(fn ($currencyGroup) => $currencyGroup->sum('amount') * 100)
                ->toArray();
        });
    }

    public function referralEarningsInUSD(): Attribute
    {
        return Attribute::make(get: fn () => app(CurrencyConversionService::class)->convertToUSD($this->referralEarningsByCurrency));
    }

    public function formattedReferralEarningsInUSD(): Attribute
    {
        return Attribute::make(get: fn () => money($this->referralEarningsInUSD, 'USD'));
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'concierge_id')
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED]);
    }

    /**
     * @return HasOneThrough<\App\Models\Concierge, User, $this>
     */
    public function referringConcierge(): HasOneThrough
    {
        return $this->hasOneThrough(
            self::class,
            User::class,
            'id',
            'id',
            'user_id',
            'concierge_referral_id'
        );
    }

    /**
     * @return HasManyThrough<\App\Models\Concierge, User, $this>
     */
    public function concierges(): HasManyThrough
    {
        return $this->hasManyThrough(
            self::class,
            User::class,
            'concierge_referral_id',
        );
    }

    /**
     * @return HasManyThrough<Referral, User, $this>
     */
    public function referrals(): HasManyThrough
    {
        return $this->hasManyThrough(
            Referral::class,
            User::class,
            'id', // Foreign key on the user's table...
            'referrer_id', // Foreign key on the referral table...
            'user_id', // Local key on the concierge table...
            'id' // Local key on the user's table...
        );
    }

    /**
     * Description
     *
     * @return HasMany<VipCode, $this>
     */
    public function vipCodes(): HasMany
    {
        return $this->hasMany(VipCode::class);
    }

    /**
     * @return BelongsTo<VenueGroup, $this>
     */
    public function venueGroup(): BelongsTo
    {
        return $this->belongsTo(VenueGroup::class);
    }
}
