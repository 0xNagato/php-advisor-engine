<?php

namespace App\Actions\Region;

use App\Models\Region;
use Lorisleiva\Actions\Concerns\AsAction;

class GetUserRegion
{
    use AsAction;

    public function handle(): Region
    {
        $regionId = auth()->user()?->region ?? config('app.default_region');

        return Region::query()->where('id', $regionId)->firstOrFail();
    }
}
