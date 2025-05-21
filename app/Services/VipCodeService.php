<?php

namespace App\Services;

use App\Models\VipCode;

class VipCodeService
{
    public function findByCode(string $code): VipCode
    {
        return VipCode::with('concierge.user')
            ->active()
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();
    }
}
