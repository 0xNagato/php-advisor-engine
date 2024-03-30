<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use libphonenumber\PhoneNumberFormat;

class ConciergeReferral extends Model
{
    use HasUuids;
    use Notifiable;

    protected $fillable = [
        'concierge_id',
        'email',
        'phone',
        'secured_at',
        'user_id',
    ];

    public function concierge(): BelongsTo
    {
        return $this->belongsTo(Concierge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routeNotificationForTwilio(): string
    {
        return $this->phone ?? '';
    }

    public function getHasSecuredAttribute(): bool
    {
        return ! blank($this->secured_at);
    }

    public function getLabelAttribute(): string
    {
        return $this->has_secured ? $this->user->name : $this->email ?? phone($this->phone, ['US', 'CA'], PhoneNumberFormat::NATIONAL);
    }
}
