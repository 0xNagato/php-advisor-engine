<?php

namespace App\Actions\Region;

use App\Models\Region;
use Lorisleiva\Actions\Concerns\AsAction;

class GetUserRegion
{
    use AsAction;

    public function handle(): Region
    {
        $sessionRegion = session('region');
        $userRegion = request()->user()?->region;
        $defaultRegion = config('app.default_region');

        $regionId = $sessionRegion ?? ($userRegion ?: $defaultRegion);

        logger()?->info('GetUserRegion: Determining region', [
            'sessionRegion' => $sessionRegion,
            'userRegion' => $userRegion,
            'defaultRegion' => $defaultRegion,
            'regionId' => $regionId,
        ]);

        return Region::query()->where('id', $regionId)->firstOrFail();
    }
}
