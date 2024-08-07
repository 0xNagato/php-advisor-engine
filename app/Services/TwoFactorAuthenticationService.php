<?php

namespace App\Services;

use App\Models\Device;
use App\Models\User;
use App\Notifications\User\SendTwoFactorCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class TwoFactorAuthenticationService
{
    private const int COOLDOWN_MINUTES = 2;

    /**
     * @throws Throwable
     */
    public function generateCode(User $user): ?int
    {
        if ($this->isWithinCooldownPeriod($user)) {
            return null;
        }

        $code = $user->generateTwoFactorCode();
        $user->notify(new SendTwoFactorCode($code));

        return $code;
    }

    public function isWithinCooldownPeriod(User $user): bool
    {
        if (! $user->userCode || ! $user->userCode->updated_at) {
            return false;
        }

        return $user->userCode->updated_at->addMinutes(self::COOLDOWN_MINUTES)->isFuture();
    }

    public function getNextCodeAvailableAt(User $user): ?Carbon
    {
        if ($this->isWithinCooldownPeriod($user)) {
            return $user->userCode->updated_at->addMinutes(self::COOLDOWN_MINUTES);
        }

        return null;
    }

    public function verifyCode(User $user, string $code): bool
    {
        return $user->userCode->code === $code;
    }

    public function registerDevice(User $user, Request $request): Device
    {
        $deviceKey = $this->generateDeviceKey($user, $request);

        return $user->devices()->firstOrCreate(
            ['key' => $deviceKey],
            ['verified' => false]
        );
    }

    public function markDeviceAsVerified(User $user, Request $request): void
    {
        $deviceKey = $this->generateDeviceKey($user, $request);

        $user->devices()
            ->where('key', $deviceKey)
            ->update(['verified' => true]);
    }

    public function isDeviceVerified(User $user, Request $request): bool
    {
        return $user->devices()
            ->where('key', $this->generateDeviceKey($user, $request))
            ->where('verified', true)
            ->exists();
    }

    private function generateDeviceKey(User $user, Request $request): string
    {
        return md5($request->userAgent().$request->ip().$user->id);
    }
}
