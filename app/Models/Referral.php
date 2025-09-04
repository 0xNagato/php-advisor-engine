<?php

namespace App\Models;

use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Filament\Resources\PartnerResource\Pages\ViewPartner;
use App\Filament\Resources\VenueResource\Pages\ViewVenue;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Referral extends Model
{
    use FormatsPhoneNumber;
    use HasFactory;
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
        'reminded_at',
        'region_id',
        'company_name',
        'qr_code_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'secured_at' => 'datetime',
            'meta' => AsArrayObject::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<QrCode, $this>
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
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

    protected function viewRoute(): Attribute
    {
        return Attribute::make(get: fn () => match ($this->type) {
            'concierge' => $this->user->concierge ? ViewConcierge::getUrl(['record' => $this->user->concierge]) : null,
            'venue' => $this->user->venue ? ViewVenue::getUrl(['record' => $this->user->venue]) : null,
            default => null,
        });
    }

    protected function referrerRoute(): Attribute
    {
        return Attribute::make(get: fn () => match ($this->referrer_type) {
            'concierge' => $this->referrer->concierge ? ViewConcierge::getUrl(['record' => $this->referrer->concierge]) : null,
            'partner' => $this->referrer->partner ? ViewPartner::getUrl(['record' => $this->referrer->partner]) : null,
            default => null,
        });
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->secured_at === null) {
                    return 'Pending';
                }

                return 'Accepted';
            }
        );
    }
}
