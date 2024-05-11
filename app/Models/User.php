<?php

namespace App\Models;

use App\Services\SmsService;
use App\Traits\FormatsPhoneNumber;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use AuthenticationLoggable;
    use FormatsPhoneNumber;
    use HasFactory;
    use HasPanelShield;
    use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'profile_photo_path',
        'payout',
        'charity_percentage',
        'partner_referral_id',
        'concierge_referral_id',
        'timezone',
        'secured_at',
        'address_1',
        'address_2',
        'city',
        'state',
        'zip',
        'country',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'secured_at' => 'datetime',
        'payout' => AsArrayObject::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'main_role',
        'name',
        'has_secured',
        'label',
    ];

    public function concierge(): HasOne
    {
        return $this->hasOne(Concierge::class);
    }

    public function restaurant(): HasOne
    {
        return $this->hasOne(Restaurant::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo_path
            ? Storage::disk('do')
                ->url($this->profile_photo_path)
            : "https://ui-avatars.com/api/?background=312596&color=fff&name=$this->name";
    }

    public function routeNotificationForTwilio(): string
    {
        return $this->phone;
    }

    public function getNameAttribute(): string
    {
        return $this->getFilamentName();
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->getFilamentAvatarUrl();
    }

    public function getFilamentName(): string
    {
        return "$this->first_name $this->last_name";
    }

    public function getMainRoleAttribute(): string
    {
        /**
         * @var Role $role
         */
        $role = self::with('roles')
            ->find($this->id)
            ->roles
            ->firstWhere('name', '!=', 'panel_user');

        return Str::of($role->name)
            ->snake()
            ->replace('_', ' ')
            ->title();
    }

    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }

    public function referrer(): HasOneThrough
    {
        return $this->hasOneThrough(__CLASS__, Referral::class, 'user_id', 'id', 'id', 'referrer_id');
    }

    public function getHasSecuredAttribute(): bool
    {
        return ! blank($this->secured_at);
    }

    public function getLabelAttribute(): string
    {
        return $this->has_secured ? $this->getFilamentName() : $this->email;
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    public function sentAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'sender_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getUnreadMessageCountAttribute(): int
    {
        return $this->messages()->whereNull('read_at')->count();
    }

    public function getLocalFormattedPhoneAttribute(): string
    {
        return $this->getLocalFormattedPhoneNumber($this->phone);
    }

    public function getInternationalFormattedPhoneNumberAttribute(): string
    {
        return $this->getInternationalFormattedPhoneNumber($this->phone);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function twofacode(): HasOne
    {
        return $this->hasOne(Twofacode::class);
    }

    public function generateCode()
    {
        $code = rand(100000, 999999);

        Twofacode::updateOrCreate(
            ['user_id' => $this->id], // field to find
            ['code' => $code] // field to update
        );

        //        app(SmsService::class)->sendMessage(
        //            auth()->user()->phone,
        //            "Do not share this code with anyone. Your 2FA login code for Prima is " . $code
        //        );
    }

    public function verify2FACode($code): bool
    {
        return $this->twofacode->code === $code;
    }

    public function markDeviceAsVerified()
    {
        $deviceKey = $this->deviceKey();

        $this->devices()
            ->where('key', $deviceKey)
            ->update(['verified' => true]);

        session()->put('twofacode'.$this->id, true);
    }

    public function registerDevice()
    {
        $deviceKey = $this->deviceKey();

        return $this->devices()->firstOrCreate(
            ['key' => $deviceKey],
            ['verified' => false]
        );
    }

    public function deviceKey()
    {
        return md5(request()->userAgent().request()->ip());
    }
}
