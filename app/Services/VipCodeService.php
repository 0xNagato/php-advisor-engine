<?php

namespace App\Services;

use App\Models\VipCode;

class VipCodeService
{
    public function findByCode(string $code)
    {
        return VipCode::with('concierge.user')
            ->active()
            ->where('code', strtoupper($code))
            ->first();
    }
}
