<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Services\CurrencyConversionService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperVipCode
 */
class VipCode extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'concierge_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function link(): Attribute
    {
        return Attribute::make(get: fn () => route('v.booking', $this->code));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @return BelongsTo<Concierge, $this>
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED]);
    }

    /**
     * @return HasManyThrough<Earning, Booking, $this>
     */
    public function earnings(): HasManyThrough
    {
        return $this->hasManyThrough(Earning::class, Booking::class)
            ->whereHas('booking', function (Builder $query) {
                $query->confirmed();
            })
            ->whereIn('earnings.type', ['concierge', 'concierge_bounty']);
    }

    public function totalEarningsGroupedByCurrency(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->earnings()
                ->selectRaw('SUM(earnings.amount) as total, earnings.currency')
                ->groupBy('earnings.currency', 'bookings.vip_code_id') // Add vip_code_id to the group by clause
                ->get()
                ->mapWithKeys(fn ($item) => [$item->currency => $item->total])
        );
    }

    public function totalEarningsInUSD(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currencyService = app(CurrencyConversionService::class);
                $earnings = $this->totalEarningsGroupedByCurrency;

                return $currencyService->convertToUSD($earnings->toArray());
            }
        );
    }

    /**
     * @return HasMany<VipSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(VipSession::class);
    }

    /**
     * Generate a new session token for this VIP code
     */
    public function generateSessionToken(): string
    {
        $token = Str::random(64);

        $this->sessions()->create([
            'token' => hash('sha256', $token),
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
        ]);

        return $token;
    }

    /**
     * Validate a session token
     */
    public function validateSessionToken(string $token): bool
    {
        return $this->sessions()
            ->where('token', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Clean up expired sessions
     */
    public function cleanExpiredSessions(): void
    {
        $this->sessions()
            ->where('expires_at', '<', now())
            ->delete();
    }
}
