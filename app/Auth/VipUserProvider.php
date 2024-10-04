<?php

namespace App\Auth;

use App\Models\VipCode;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use SensitiveParameter;

class VipUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?VipCode
    {
        return VipCode::query()->find($identifier);
    }

    public function retrieveByToken($identifier, $token): null
    {
        return null; // Not implemented for this use case
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Not implemented for this use case
    }

    public function retrieveByCredentials(array $credentials): VipCode|Authenticatable|null
    {
        if (blank($credentials['code'])) {
            return null;
        }

        $code = strtolower((string) $credentials['code']);

        return VipCode::query()->whereRaw('LOWER(code) = ?', [$code])
            ->where('is_active', true)->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return strtolower($user->code) === strtolower((string) $credentials['code']);
    }

    public function rehashPasswordIfRequired(
        Authenticatable $user,
        #[SensitiveParameter] array $credentials,
        bool $force = false
    ) {}
}
