<?php

namespace App\Traits;

use App\Models\User;
use Spatie\Activitylog\ActivityLogger;

trait LogsImpersonatedActivity
{
    protected function getActivityLogger(): ActivityLogger
    {
        $logger = activity();

        if (auth()->check() && session()->has('impersonated_by')) {
            $impersonatorId = session()->get('impersonated_by');
            $impersonator = User::query()->find($impersonatorId);

            if ($impersonator) {
                $logger->causedBy($impersonator)
                    ->withProperties(fn (array $properties) => array_merge($properties, [
                        'impersonator_id' => $impersonator->id,
                        'impersonator_name' => $impersonator->name,
                        'impersonator_email' => $impersonator->email,
                        'impersonated_user_id' => auth()->id(),
                        'impersonated_user_name' => auth()->user()->name,
                        'impersonated_user_email' => auth()->user()->email,
                    ]));
            }
        }

        return $logger;
    }
}
