<?php

namespace App\Services;

use App\Models\VipCode;
use Illuminate\Support\Facades\Auth;

class VipAuthenticationService
{
    public function authenticate(string $code): ?VipCode
    {
        $code = strtoupper($code);
        if (Auth::guard('vip_code')->attempt(['code' => $code, 'is_active' => true])) {
            return Auth::guard('vip_code')->user();
        }

        return null;
    }

    public function login(VipCode $vipCode): void
    {
        Auth::guard('vip_code')->login($vipCode);
    }
}
