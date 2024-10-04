<?php

namespace App\Services;

use App\Models\VipCode;
use Illuminate\Support\Facades\Auth;

class VipAuthenticationService
{
    public function authenticate(string $code): ?VipCode
    {
        $code = strtoupper($code);
        if (Auth::guard('vip_code')->attempt(['code' => $code])) {
            return Auth::guard('vip_code')->user();
        }

        return null;
    }

    public function login(VipCode $vipCode): void
    {
        Auth::guard('vip_code')->login($vipCode);
    }

    public function setSessionData(VipCode $vipCode): void
    {
        session([
            'vip_code_id' => $vipCode->id,
            'vip_code' => $vipCode->code,
            'concierge_id' => $vipCode->concierge_id,
        ]);
    }
}
