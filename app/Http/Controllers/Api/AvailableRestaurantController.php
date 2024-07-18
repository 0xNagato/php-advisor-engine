<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Restaurant;
use App\Traits\ManagesBookingForms;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailableRestaurantController extends Controller
{
    use ManagesBookingForms;

    public array $timeslotHeaders = [];

    public function __invoke(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $validated = $request->validate([
            'date' => 'required|date',
            'guest_count' => 'required|integer|min:2',
            'reservation_time' => 'required|date_format:H:i:s',
        ]);

        $date = $validated['date'];
        $guestCount = $validated['guest_count'];
        $reservationTime = $validated['reservation_time'];

        $region = Region::query()->find(session('region', 'miami'));
        $this->timezone = $region->timezone;

        $requestedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $this->timezone);
        $currentDate = Carbon::now($this->timezone);

        if ($currentDate->isSameDay($requestedDate)) {
            $reservationTime = $this->adjustReservationTime($reservationTime);
        }

        $endTime = $this->calculateEndTime($reservationTime);
        $this->fillTimeslotHeaders($reservationTime, $endTime);

        $guestCount = $this->calculateGuestCount($guestCount);

        $restaurants = $this->getAvailableRestaurants($guestCount, $reservationTime, $endTime, $date);

        return response()->json([
            'available_restaurants' => $restaurants,
            'timeslot_headers' => $this->timeslotHeaders,
        ]);
    }

    /**
     * @return Collection<Restaurant>
     */
    public function restaurants($guestCount, $reservationTime, $endTime, $date): Collection
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
}
