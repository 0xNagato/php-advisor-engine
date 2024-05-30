<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperEarning
 */
class Earning extends Model
{
    protected $fillable = [
        'user_id',
        'booking_id',
        'payment_id',
        'type',
        'amount',
        'currency',
        'percentage',
        'percentage_of',
        'confirmed_at',
    ];

    /**
     * @return BelongsTo<User, \App\Models\Earning>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Booking, \App\Models\Earning>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Payment, \App\Models\Earning>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at');
    }
}
