<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reservation_id',
        'concierge_user_id',
        'guest_user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_count',
        'total_fee',
        'currency',
        'status',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function conciergeProfile(): BelongsTo
    {
        return $this->belongsTo(ConciergeProfile::class, 'concierge_user_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
