<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsResponse extends Model
{
    protected $fillable = [
        'message',
        'phone_number',
        'response',
    ];

    /**
     * @return BelongsTo<Booking, SmsResponse>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
