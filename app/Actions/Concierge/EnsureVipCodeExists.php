<?php

namespace App\Actions\Concierge;

use App\Models\Concierge;
use App\Models\VipCode;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class EnsureVipCodeExists
{
    use AsAction;

    public function handle(Concierge $concierge): void
    {
        $existingCode = VipCode::query()->where('concierge_id', $concierge->id)->first();

        if (! $existingCode) {
            $randomCode = strtoupper(Str::random(6));

            VipCode::query()->create([
                'code' => $randomCode,
                'concierge_id' => $concierge->id,
            ]);
        }
    }
}
