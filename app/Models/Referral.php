<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

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
        return !blank($this->secured_at);
    }

    public function getPhoneAttribute($value): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $numberProto = $phoneUtil->parse($value, "US");

            // Check if the number is valid
            if ($phoneUtil->isValidNumber($numberProto)) {
                // Format the number in the US/CA national format
                return $phoneUtil->format($numberProto, PhoneNumberFormat::NATIONAL);
            }

            $repairedNumber = "+1" . $value;
            $numberProto = $phoneUtil->parse($repairedNumber, "US");

            if ($phoneUtil->isValidNumber($numberProto)) {
                return $phoneUtil->format($numberProto, PhoneNumberFormat::NATIONAL);
            }
        } catch (NumberParseException $e) {
            Logger::error("Error parsing phone number: " . $e->getMessage());
            // If parsing fails, return the original number
            return $value;
        }

        // Return the original input if all else fails
        return $value;
    }

    public function getLabelAttribute(): string
    {
        return $this->has_secured ? $this->user->name : $this->email ?? $this->phone;
    }
}
