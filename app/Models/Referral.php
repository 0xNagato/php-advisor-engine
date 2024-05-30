<?php

namespace App\Models;

use App\Traits\FormatsPhoneNumber;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

/**
 * @mixin IdeHelperReferral
 */
class Referral extends Model
{
    use FormatsPhoneNumber;
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
        'local_formatted_phone',
        'first_name',
        'last_name',
        'notified_at',
    ];

    /**
     * @return BelongsTo<User, \App\Models\Referral>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * @return BelongsTo<User, \App\Models\Referral>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routeNotificationForTwilio(): string
    {
        return $this->phone ?? '';
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => $this->first_name.' '.$this->last_name);
    }

    protected function hasSecured(): Attribute
    {
        return Attribute::make(get: fn () => ! blank($this->secured_at));
    }

    protected function label(): Attribute
    {
        return Attribute::make(get: fn () => $this->has_secured ? $this->user->name : $this->email ?? $this->local_formatted_phone);
    }

    protected function localFormattedPhone(): Attribute
    {
        return Attribute::make(get: fn () => $this->getLocalFormattedPhoneNumber($this->phone));
    }
}
