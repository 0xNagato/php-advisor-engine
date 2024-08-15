<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Actions\Reservations\GetReservationTimeOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TimeslotRequest;
use Illuminate\Http\JsonResponse;

class TimeslotController extends Controller
{
    public function __invoke(TimeslotRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->availableTimeslots(date: $request->validated()['date']),
        ]);
    }

    private function availableTimeslots($date): array
    {
        // Get the user's current date and time
        $region = GetUserRegion::run();
        $isCurrentDay = $date === now($region->timezone)->format('Y-m-d');
        $currentTime = now($region->timezone)->format('H:i:s');

        $timeslots = GetReservationTimeOptions::run(date: $date);

        return collect($timeslots)
            ->map(fn ($formattedTime, $time) => [
                'label' => $formattedTime,
                'value' => $time,
                'available' => ! ($isCurrentDay && $time < $currentTime),
            ])
            ->values()
            ->all();
    }
}
