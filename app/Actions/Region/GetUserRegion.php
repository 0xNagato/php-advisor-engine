<?php

namespace App\Actions\Region;

use App\Models\Region;
use Lorisleiva\Actions\Concerns\AsAction;

class GetUserRegion
{
    use AsAction;

    public function handle(): Region
    {
        return Region::where('id', request()->user()?->region ?? 'miami')->first();
    }
}
