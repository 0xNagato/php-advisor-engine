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
        $date = $request->input('date');
        $region = GetUserRegion::run();
        $currentDate = now($region->timezone)->format('Y-m-d');

        if (! $date || $date < $currentDate) {
            // If date is invalid or in the past, return all times as unavailable
            return response()->json([
                'data' => $this->allTimesUnavailable(),
            ]);
        }

        return response()->json([
            'data' => $this->availableTimeslots(date: $date),
        ]);
    }

    private function allTimesUnavailable(): array
    {
        $timeslots = GetReservationTimeOptions::run(date: now()->format('Y-m-d'));

        return collect($timeslots)
            ->map(fn ($formattedTime, $time) => [
                'label' => $formattedTime,
                'value' => $time,
                'available' => false,
            ])
            ->values()
            ->all();
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
