<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSpecialPricingVenue
 */
class SpecialPricingVenue extends Model
{
    protected $fillable = [
        'venue_id',
        'date',
        'fee',
    ];

    /**
     * @return BelongsTo<Venue, SpecialPricingVenue>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
