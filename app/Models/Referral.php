<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use libphonenumber\PhoneNumberFormat;

class Referral extends Model
{
    use HasUuids;
    use Notifiable;

    protected $fillable = [
        'referrer_id',
        'email',
        'phone',
        'secured_at',
        'user_id',
        'type',
        'referrer_type',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
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
