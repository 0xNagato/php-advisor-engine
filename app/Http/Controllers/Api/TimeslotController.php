<?php

namespace App\Http\Controllers\Api;

use App\Actions\Region\GetUserRegion;
use App\Actions\Reservations\GetReservationTimeOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TimeslotRequest;
use App\Models\Region;
use App\OpenApi\Parameters\TimeslotParameter;
use App\OpenApi\Responses\TimeslotResponse;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Parameters;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
/**
 * Timeslot Controller
 *
 * Handles retrieving available or unavailable timeslots
 * for reservations based on user-specified date and region.
 * Provides timeslot availability and ensures time sensitivity
 * according to the region's timezone.
 */
class TimeslotController extends Controller
{
    /**
     * Retrieve available or unavailable timeslots for reservations.
     *
     * This endpoint fetches a list of timeslots based on the requested date
     * and user region. If the date is either invalid or in the past, all times
     * are marked as unavailable.
     *
     * @param  TimeslotRequest  $request  The validated request containing the date and optional region ID.
     * @return JsonResponse A JSON response containing the timeslots, specifying whether each timeslot is available.
     */
    #[OpenApi\Operation(
        tags: ['Timeslots'],
    )]
    #[Parameters(factory: TimeslotParameter::class)]
    #[OpenApiResponse(factory: TimeslotResponse::class)]
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
        $currentTime = now($region->timezone);
        $minimumBookingTime = $currentTime->copy()->addMinutes(ReservationService::MINUTES_PAST);

        $timeslots = GetReservationTimeOptions::run(date: $date);

        return collect($timeslots)
            ->map(function ($formattedTime, $time) use ($isCurrentDay, $minimumBookingTime, $date, $region) {
                // For current day, check if the timeslot is more than MINUTES_PAST in the future
                if ($isCurrentDay) {
                    $timeslotDateTime = Carbon::parse($date.' '.$time, $region->timezone);
                    $available = $timeslotDateTime->gt($minimumBookingTime);
                } else {
                    // For future dates, all timeslots are available
                    $available = true;
                }

                return [
                    'label' => $formattedTime,
                    'value' => $time,
                    'available' => $available,
                ];
            })
            ->values()
            ->all();
    }
}
