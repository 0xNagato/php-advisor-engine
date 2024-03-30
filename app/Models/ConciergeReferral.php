<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class ConciergeReferral extends Model
{
    use HasUuids;
    use Notifiable;

    protected $fillable = [
        'concierge_id',
        'email',
        'phone',
    ];

    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    public function routeNotificationForTwilio(): string
    {
        return $this->phone ?? '';
    }
}
