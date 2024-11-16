<?php

/** @noinspection PhpUnused */
/** @noinspection PhpUnreachableStatementInspection */

namespace App\Models;

use App\Data\NotificationPreferencesData;
use App\Traits\FormatsPhoneNumber;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Sanctum\HasApiTokens;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Throwable;

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
     * @return HasOne<Concierge, $this>
     */
    public function concierge(): HasOne
    {
        return $this->hasOne(Concierge::class);
    }

    /**
     * @return HasOne<Venue, $this>
     */
    public function venue(): HasOne
    {
        return $this->hasOne(Venue::class);
    }

    /**
     * @return HasOne<Partner, $this>
     */
    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * @return HasOne<Referral, $this>
     */
    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }

    /**
     * @return HasOneThrough<User, Referral, $this>
     */
    public function referrer(): HasOneThrough
    {
        return $this->hasOneThrough(self::class, Referral::class, 'user_id', 'id', 'id', 'referrer_id');
    }

    /**
     * @return HasMany<Earning, $this>
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    /**
     * @return HasMany<Announcement, $this>
     */
    public function sentAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'sender_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<Device, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * @return HasOne<UserCode, $this>
     */
    public function userCode(): HasOne
    {
        return $this->hasOne(UserCode::class);
    }

    /**
     * @return HasMany<RoleProfile, $this>
     */
    public function roleProfiles(): HasMany
    {
        return $this->hasMany(RoleProfile::class);
    }

    /**
     * @return HasOne<RoleProfile>
     */
    public function activeProfile(): HasOne
    {
        return $this->hasOne(RoleProfile::class)->where('is_active', true);
    }

    // Filament-related methods
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
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

    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
    protected function mainRole(): Attribute
    {
        return Attribute::make(
            get: function () {
                $activeProfile = $this->activeProfile;
                if (! $activeProfile) {
                    return null;
                }

                return Str::of($activeProfile->role->name)
                    ->snake()
                    ->replace('_', ' ')
                    ->title();
            }
        );
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
        return UserCode::generateCodeForUser($this);
    }

    /**
     * Assign the given role(s) to the user and create corresponding profiles.
     */
    public function assignRole(Role|string|int|array|Collection $roles): static
    {
        // Convert single items to array
        if (is_string($roles) || $roles instanceof Role || is_int($roles)) {
            $roles = [$roles];
        }

        /** @var Collection<int, Role> $roleModels */
        $roleModels = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if ($role instanceof Role) {
                    return $role;
                }

                $query = Role::query();

                // If numeric, search by ID, otherwise search by name
                if (is_numeric($role)) {
                    return $query->find($role);
                }

                return $query->where('name', $role)->first();
            })
            ->filter();

        // Sync roles
        $this->roles()->sync($roleModels->pluck('id')->toArray(), false);
        $this->forgetCachedPermissions();

        // Check if user has any active profile
        $hasActiveProfile = $this->roleProfiles()->where('is_active', true)->exists();

        // Create profiles for core roles if they don't exist
        foreach ($roleModels as $role) {
            if (in_array($role->name, ['super_admin', 'venue', 'partner', 'concierge']) && ! $this->roleProfiles()->where('role_id', $role->id)->exists()) {
                $this->roleProfiles()->create([
                    'role_id' => $role->id,
                    'name' => ucfirst($role->name).' Profile',
                    'is_active' => ! $hasActiveProfile,
                ]);

                if (! $hasActiveProfile) {
                    $hasActiveProfile = true;
                }
            }
        }

        return $this;
    }

    /**
     * Switch to a different role profile.
     *
     * @throws InvalidArgumentException If profile doesn't belong to user
     * @throws Throwable
     */
    public function switchProfile(RoleProfile $profile): void
    {
        throw_unless($this->roleProfiles->contains($profile), new InvalidArgumentException('Profile does not belong to this user'));

        DB::transaction(function () use ($profile): void {
            $this->roleProfiles()->update(['is_active' => false]);
            $profile->update(['is_active' => true]);
        });
    }

    public function hasActiveRole(array|string $roles): bool
    {
        $activeProfile = $this->activeProfile;
        if (! $activeProfile) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($activeProfile->role->name, $roles, true);
    }
}
