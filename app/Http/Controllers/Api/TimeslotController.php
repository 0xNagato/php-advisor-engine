<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Actions\Reservations\GetReservationTimeOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TimeslotRequest;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

class TimeslotController extends Controller
{
    public function __invoke(TimeslotRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $date = $validatedData['date'];

        // Get the region - either from the request parameter or fallback to user's default region
        $region = null;
        if (isset($validatedData['region'])) {
            $region = Region::query()->where('id', $validatedData['region'])->first();
        }
        $region = $region ?: GetUserRegion::run();

        $currentDate = now($region->timezone)->format('Y-m-d');

        if (! $date || $date < $currentDate) {
            // If the date is invalid or in the past, return all times as unavailable
            return response()->json([
                'data' => $this->allTimesUnavailable($region),
            ]);
        }

        return response()->json([
            'data' => $this->availableTimeslots(date: $date, region: $region),
        ]);
    }

    private function allTimesUnavailable(Region $region): array
    {
        $timeslots = GetReservationTimeOptions::run(date: now($region->timezone)->format('Y-m-d'));

        return collect($timeslots)
            ->map(fn ($formattedTime, $time) => [
                'label' => $formattedTime,
                'value' => $time,
                'available' => false,
            ])
            ->values()
            ->all();
    }

    private function availableTimeslots(string $date, Region $region): array
    {
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
