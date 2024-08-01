<?php

namespace App\Rules;

use App\Models\Region;
use Illuminate\Contracts\Validation\Rule;

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
