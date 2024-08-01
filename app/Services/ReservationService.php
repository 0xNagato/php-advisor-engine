<?php

namespace App\Services;

use App\Actions\Region\GetUserRegion;
use App\Models\Region;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ReservationService
{
    private readonly Region $region;

    public const int MINUTES_PAST = 60;

    public const int MINUTES_FUTURE = 120;

    public function __construct(
        public string|Carbon $date,
        public int $guestCount,
        public string $reservationTime,
    ) {
        $this->region = GetUserRegion::run();
    }

    public function getAvailableRestaurants(): Collection
    {
        $requestedDate = Carbon::createFromFormat('Y-m-d', $this->date, $this->region->timezone);

        $currentDate = Carbon::now($this->region->timezone);

        if ($currentDate->isSameDay($requestedDate)) {
            $this->reservationTime = $this->adjustTime();
        }

        return Restaurant::available()
            ->where('region', $this->region->id)
            ->with(['schedules' => function ($query) {
                $query->where('booking_date', $this->date)
                    ->where('party_size', $this->getGuestCount())
                    ->where('start_time', '>=', $this->reservationTime)
                    ->where('start_time', '<=', $this->calculateEndTime());
            }])
            ->get();
    }

    public function adjustTime(): string
    {
        $reservationTime = Carbon::createFromFormat('H:i:s', $this->reservationTime, $this->region->timezone);

        if ($reservationTime->copy()->subMinutes(self::MINUTES_PAST)->gt(Carbon::now($this->region->timezone))) {
            return $reservationTime->subMinutes(self::MINUTES_PAST)->format('H:i:s');
        }

        return $reservationTime->format('H:i:s');
    }

    public function calculateEndTime(): string
    {
        $endTime = Carbon::createFromFormat(
            'H:i:s',
            $this->reservationTime,
            $this->region->timezone
        )->addMinutes(self::MINUTES_FUTURE);

        $limitTime = Carbon::createFromTime(23, 59, 0, $this->region->timezone);

        return $endTime->gt($limitTime) ? '23:59:59' : $endTime->format('H:i:s');
    }

    public function getTimeslotHeaders(): array
    {
        $headers = [];
        $start = Carbon::createFromFormat('H:i:s', $this->reservationTime);
        $end = Carbon::createFromFormat('H:i:s', $this->calculateEndTime());

        for ($time = $start; $time->lte($end); $time->addMinutes(30)) {
            $headers[$time->format('H:i:s')] = $time->format('g:i A');
        }

        return $headers;
    }

    public function getGuestCount(): int
    {
        return $this->guestCount % 2 !== 0 ? $this->guestCount + 1 : $this->guestCount;
    }
}
