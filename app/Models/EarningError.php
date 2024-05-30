<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperEarningError
 */
class EarningError extends Model
{
    protected $fillable = [
        'booking_id',
        'error_message',
        'restaurant_earnings',
        'concierge_earnings',
        'concierge_referral_level_1_earnings',
        'concierge_referral_level_2_earnings',
        'restaurant_partner_earnings',
        'concierge_partner_earnings',
        'platform_earnings',
        'total_local',
        'total_fee',
    ];

    /**
     * @return BelongsTo<Booking, \App\Models\EarningError>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
