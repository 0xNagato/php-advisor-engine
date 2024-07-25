<?php

namespace App\Http\Controllers\Api;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Restaurant;
use App\Traits\ManagesBookingForms;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AvailabilityCalendarController extends Controller
{
    use ManagesBookingForms;

    public array $timeslotHeaders = [];

    private string $currency;

    public function __invoke(Request $request): JsonResponse
    {
        $validatedData = $this->validateReservationData($request);
        if ($validatedData instanceof JsonResponse) {
            return $validatedData;
        }

        $date = $validatedData['date'];
        $guestCount = $validatedData['guest_count'];
        $reservationTime = $validatedData['reservation_time'];

        $region = Region::query()->find(session('region', 'miami'));
        $this->timezone = $region->timezone;
        $this->currency = $region->currency;

        $requestedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $this->timezone);
        $currentDate = Carbon::now($this->timezone);

        if ($currentDate->isSameDay($requestedDate)) {
            $reservationTime = $this->adjustReservationTime($reservationTime);
        }

        $endTime = $this->calculateEndTime($reservationTime);
        $this->fillTimeslotHeaders($reservationTime, $endTime);

        $guestCount = $this->calculateGuestCount($guestCount);

        return response()->json([
            'data' => [
                // I left this one commented, so we can compare the difference between the two methods if needed
                //            'available_restaurants' => $this->getAvailableRestaurants($guestCount, $reservationTime, $endTime, $date),
                'available_restaurants' => $this->getAvailableRestaurantsFiltered($guestCount, $reservationTime, $endTime, $date),
                'timeslot_headers' => $this->timeslotHeaders,
            ]
        ]);
    }

    private function adjustReservationTime($reservationTime): string
    {
        $reservationTime = \Carbon\Carbon::createFromFormat('H:i:s', $reservationTime, $this->timezone);
        $currentTime = Carbon::now($this->timezone);

        if ($reservationTime->copy()->subMinutes(self::MINUTES_PAST)->gt($currentTime)) {
            return $reservationTime->subMinutes(self::MINUTES_PAST)->format('H:i:s');
        }

        return $reservationTime->format('H:i:s');
    }

    private function calculateEndTime($reservationTime): string
    {
        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $reservationTime, $this->timezone)?->addMinutes(self::MINUTES_FUTURE);
        $limitTime = Carbon::createFromTime(23, 59, 0, $this->timezone);

        return $endTime->gt($limitTime) ? '23:59:59' : $endTime->format('H:i:s');
    }

    public function fillTimeslotHeaders($reservationTime, $endTime): void
    {
        $this->timeslotHeaders = [];
        $start = \Carbon\Carbon::createFromFormat('H:i:s', $reservationTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);

        for ($time = $start; $time->lte($end); $time->addMinutes(30)) {
            $this->timeslotHeaders[$time->format('H:i:s')] = $time->format('g:i A');
        }
    }

    private function calculateGuestCount($guestCount): int
    {
        return (int) ($guestCount % 2 !== 0 ? $guestCount + 1 : $guestCount);
    }

    /**
     * @return Collection<Restaurant>
     */
    private function getAvailableRestaurants($guestCount, $reservationTime, $endTime, $date): Collection
    {
        return Restaurant::available()
            ->where('region', session('region', 'miami'))
            ->with(['schedules' => function ($query) use ($guestCount, $reservationTime, $endTime, $date) {
                $query->where('booking_date', $date)
                    ->where('party_size', $guestCount)
                    ->where('start_time', '>=', $reservationTime)
                    ->where('start_time', '<=', $endTime);
            }])->get();
    }

    private function getAvailableRestaurantsFiltered($guestCount, $reservationTime, $endTime, $date)
    {
        $restaurants = Restaurant::available()
            ->where('region', session('region', 'miami'))
            ->with(['schedules' => function ($query) use ($guestCount, $reservationTime, $endTime, $date) {
                $query->where('booking_date', $date)
                    ->where('party_size', $guestCount)
                    ->where('start_time', '>=', $reservationTime)
                    ->where('start_time', '<=', $endTime);
            }])->get();

        // Map the restaurants collection to include only the required fields
        return $restaurants->map(function ($restaurant) use ($guestCount) {
            return [
                'id' => $restaurant->id,
                'logo' => $restaurant->logo,
                'restaurant_name' => $restaurant->restaurant_name,
                'schedules' => collect($restaurant->schedules)->map(function ($schedule) use ($restaurant, $guestCount){

                    $isBookable = $schedule->is_bookable;
                    $primeTime = $schedule->prime_time;
                    $hasLowInventory = $schedule->has_low_inventory;
                    $fee = moneyWithoutCents($schedule->fee($guestCount), $this->currency);
                    $booking_date = $schedule->booking_date->format('Y-m-d');

                    return [
                        'id' => $schedule->id,
                        'is_bookable' => $isBookable,
                        'status' => $restaurant->status === RestaurantStatus::ACTIVE,
                        'booking_date' => $booking_date,
                        'prime_time' => $primeTime,
                        'has_low_inventory' => $hasLowInventory,
                        'fee' => $fee,
                    ];
                })->values()
            ];
        });
    }

    private function validateReservationData(Request $request)
    {
        $rules = [
            'date' => ['required','date'],
            'guest_count' => ['required','integer','min:2'],
            'reservation_time' => ['required','date_format:H:i:s'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $validator->validated();
    }
}
