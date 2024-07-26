<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Region;

class ActiveRegion implements Rule
{
    public function passes($attribute, $value)
    {
        $region = Region::active()->find($value);
        return $region !== null;
    }

    public function message()
    {
        return 'Region not found or not active.';
    }
}
