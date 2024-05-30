<?php

namespace App\Models;

use App\Services\SmsService;
use App\Traits\FormatsPhoneNumber;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Random\RandomException;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin IdeHelperUser
 */
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

    /**
     * @return HasOne<Concierge>
     */
    public function concierge(): HasOne
    {
        return $this->hasOne(Concierge::class);
    }

    /**
     * @return HasOne<Restaurant>
     */
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

    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => $this->getFilamentName());
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(get: fn () => $this->getFilamentAvatarUrl());
    }

    public function getFilamentName(): string
    {
        return "$this->first_name $this->last_name";
    }

    protected function mainRole(): Attribute
    {
        return Attribute::make(get: function () {
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
        });
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
     * @return HasOneThrough<\App\Models\User>
     */
    public function referrer(): HasOneThrough
    {
        return $this->hasOneThrough(self::class, Referral::class, 'user_id', 'id', 'id', 'referrer_id');
    }

    protected function hasSecured(): Attribute
    {
        return Attribute::make(get: fn () => ! blank($this->secured_at));
    }

    protected function label(): Attribute
    {
        return Attribute::make(get: fn () => $this->has_secured ? $this->getFilamentName() : $this->email);
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

    /**
     * @throws RandomException
     */
    public function generateCode(): void
    {
        $code = random_int(100000, 999999);

        UserCode::query()->updateOrCreate(
            ['user_id' => $this->id],
            // field to find
            ['code' => $code]
        );

        app(SmsService::class)->sendMessage(
            auth()->user()->phone,
            'Do not share this code with anyone. Your 2FA login code for PRIMA is '.$code
        );
    }

    public function verify2FACode($code): bool
    {
        return $this->userCode->code === $code;
    }

    public function markDeviceAsVerified(): void
    {
        $deviceKey = $this->deviceKey();

        $this->devices()
            ->where('key', $deviceKey)
            ->update(['verified' => true]);

        session()->put('usercode.'.$this->id, true);
    }

    public function registerDevice(): Model
    {
        $deviceKey = $this->deviceKey();

        return $this->devices()->firstOrCreate(
            ['key' => $deviceKey],
            ['verified' => false]
        );
    }

    public function deviceKey(): string
    {
        return md5(request()->userAgent().request()->ip().$this->id);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'secured_at' => 'datetime',
            'payout' => AsArrayObject::class,
        ];
    }
}
