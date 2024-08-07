<?php

namespace App\Models;

use App\Data\NotificationPreferencesData;
use App\Traits\FormatsPhoneNumber;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Traits\HasRoles;
use Throwable;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use AuthenticationLoggable;
    use FormatsPhoneNumber;
    use HasApiTokens;
    use HasFactory;
    use HasPanelShield;
    use HasRoles;
    use Notifiable;

    // Attributes
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'phone', 'profile_photo_path',
        'payout', 'charity_percentage', 'partner_referral_id', 'concierge_referral_id',
        'timezone', 'secured_at', 'address_1', 'address_2', 'city', 'state', 'zip',
        'country', 'preferences', 'region',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret',
    ];

    protected $appends = [
        'main_role', 'name', 'has_secured', 'label',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'secured_at' => 'datetime',
            'payout' => AsArrayObject::class,
            'preferences' => NotificationPreferencesData::class,
        ];
    }

    // Relationships
    /**
     * @return HasOne<Concierge>
     */
    public function concierge(): HasOne
    {
        return $this->hasOne(Concierge::class);
    }

    /**
     * @return HasOne<Venue>
     */
    public function venue(): HasOne
    {
        return $this->hasOne(Venue::class);
    }

    /**
     * @return HasOne<Partner>
     */
    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    /**
     * @return HasMany<Referral>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * @return HasOne<Referral>
     */
    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }

    /**
     * @return HasOneThrough<User>
     */
    public function referrer(): HasOneThrough
    {
        return $this->hasOneThrough(self::class, Referral::class, 'user_id', 'id', 'id', 'referrer_id');
    }

    /**
     * @return HasMany<Earning>
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    /**
     * @return HasMany<Announcement>
     */
    public function sentAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'sender_id');
    }

    /**
     * @return HasMany<Message>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<Device>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * @return HasOne<UserCode>
     */
    public function userCode(): HasOne
    {
        return $this->hasOne(UserCode::class);
    }

    // Filament-related methods
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo_path
            ? Storage::disk('do')->url($this->profile_photo_path)
            : "https://ui-avatars.com/api/?background=312596&color=fff&format=png&name=$this->name";
    }

    public function getFilamentName(): string
    {
        return "$this->first_name $this->last_name";
    }

    // Attribute accessors and mutators
    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => $this->getFilamentName());
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(get: fn () => $this->getFilamentAvatarUrl());
    }

    protected function mainRole(): Attribute
    {
        return Attribute::make(get: function () {
            $role = self::with('roles')
                ->find($this->id)
                ->roles
                ->firstWhere('name', '!=', 'panel_user');

            return Str::of($role->name)
                ->snake()
                ->replace('_', ' ')
                ->title();
        });
    }

    protected function hasSecured(): Attribute
    {
        return Attribute::make(get: fn () => ! blank($this->secured_at));
    }

    protected function label(): Attribute
    {
        return Attribute::make(get: fn () => $this->has_secured ? $this->getFilamentName() : $this->email);
    }

    protected function unreadMessageCount(): Attribute
    {
        return Attribute::make(get: fn () => $this->messages()->whereNull('read_at')->count());
    }

    protected function localFormattedPhone(): Attribute
    {
        return Attribute::make(get: fn () => $this->getLocalFormattedPhoneNumber($this->phone));
    }

    protected function internationalFormattedPhoneNumber(): Attribute
    {
        return Attribute::make(get: fn () => $this->getInternationalFormattedPhoneNumber($this->phone));
    }

    // Miscellaneous methods
    public function routeNotificationForTwilio(): string
    {
        return $this->phone;
    }

    public function canImpersonate(): true
    {
        return true;
    }

    /**
     * Generate a two-factor code for the user.
     *
     * @return int The generated code
     *
     * @throws Throwable
     */
    public function generateTwoFactorCode(): int
    {
        return $this->userCode::generateCodeForUser($this);
    }
}
