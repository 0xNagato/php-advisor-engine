<?php

namespace App\Actions\SpecialRequest;

use App\Data\SpecialRequest\CreateSpecialRequestData;
use App\Events\SpecialRequestCreated;
use App\Models\SpecialRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateSpecialRequest
{
    use AsAction;

    public function handle(CreateSpecialRequestData $data): SpecialRequest
    {
        $specialRequest = SpecialRequest::query()->create($data->toArray());

        SpecialRequestCreated::dispatch($specialRequest);

        return $specialRequest;
    }
}
