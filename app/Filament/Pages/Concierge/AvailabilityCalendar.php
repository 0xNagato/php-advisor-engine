<?php

namespace App\Filament\Pages\Concierge;

use App\Models\Region;
use App\Models\Restaurant;
use App\Traits\ManagesBookingForms;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;

/**
 * @property Form $form
 */
class AvailabilityCalendar extends Page
{
    use ManagesBookingForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.concierge.availability-calendar';

    protected static ?int $navigationSort = -2;

    public ?array $data = null;

    public string $currency;

    public ?string $endTimeForQuery = null;

    /**
     * @var Collection<Restaurant>|null
     */
    public ?Collection $restaurants = null;

    public array $timeslotHeaders = [];

    public function mount(): void
    {
        $region = Region::query()->find(session('region', 'miami'));
        $this->timezone = $region->timezone;
        $this->currency = $region->currency;
        $this->form->fill();

        // This is used for testing the design so you don't need to fill out the form every time
        // $this->form->fill([
        //     'date' => now(auth()->user()->timezone)->format('Y-m-d'),
        //     'radio_date' => now(auth()->user()->timezone)->format('Y-m-d'),
        //     'select_date' => now(auth()->user()->timezone)->format('Y-m-d'),
        //     'guest_count' => 2,
        //     'reservation_time' => now(auth()->user()->timezone)->format('H:i:s'),
        // ]);
        //
        // $this->restaurants = Restaurant::available()->with(['schedules' => function ($query) {
        //     $query->where('booking_date', now(auth()->user()->timezone)->format('Y-m-d'))
        //         ->where('party_size', 2)
        //         ->where('start_time', '>=', now(auth()->user()->timezone)->format('H:i:s'))
        //         ->where('start_time', '<=', now(auth()->user()->timezone)->addMinutes(150)->format('H:i:s'));
        // }])->get();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            ...$this->commonFormComponents(),
        ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('data');
    }

    public function conciergePayout(Restaurant $restaurant): int
    {
        return ($restaurant->non_prime_fee_per_head * $this->data['guest_count']) * 90;
    }

    public function updatedData($data, $key): void
    {
        if ($key === 'guest_count' && blank($data)) {
            $this->data['guest_count'] = 2;
        }

        if (isset($this->data['reservation_time'], $this->data['date'], $this->data['guest_count'])) {
            $requestedDate = Carbon::createFromFormat('Y-m-d', $this->data['date'], $this->timezone);
            $currentDate = Carbon::now($this->timezone);

            $reservationTime = $this->form->getState()['reservation_time'];
            if ($currentDate->isSameDay($requestedDate)) {
                $reservationTime = $this->adjustReservationTime($reservationTime);
            }

            $endTime = $this->calculateEndTime($reservationTime);
            $this->fillTimeslotHeaders($reservationTime, $endTime);

            $guestCount = $this->calculateGuestCount();

            $this->restaurants = $this->getAvailableRestaurants($guestCount, $reservationTime, $endTime);
        }
    }

    private function adjustReservationTime($reservationTime): string
    {
        $reservationTime = Carbon::createFromFormat('H:i:s', $reservationTime, $this->timezone);
        $currentTime = Carbon::now($this->timezone);

        if ($reservationTime->copy()->subMinutes(self::MINUTES_PAST)->gt($currentTime)) {
            return $reservationTime->subMinutes(self::MINUTES_PAST)->format('H:i:s');
        }

        return $reservationTime->format('H:i:s');
    }

    private function calculateEndTime($reservationTime): string
    {
        $endTime = Carbon::createFromFormat('H:i:s', $reservationTime, $this->timezone)?->addMinutes(self::MINUTES_FUTURE);
        $limitTime = Carbon::createFromTime(23, 59, 0, $this->timezone);

        return $endTime->gt($limitTime) ? '23:59:59' : $endTime->format('H:i:s');
    }

    public function fillTimeslotHeaders($reservationTime, $endTime): void
    {
        $this->timeslotHeaders = [];
        $start = Carbon::createFromFormat('H:i:s', $reservationTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);

        for ($time = $start; $time->lte($end); $time->addMinutes(30)) {
            $this->timeslotHeaders[$time->format('H:i:s')] = $time->format('g:i A');
        }
    }

    private function calculateGuestCount(): int
    {
        $guestCount = ceil($this->form->getState()['guest_count']);

        return (int) ($guestCount % 2 !== 0 ? $guestCount + 1 : $guestCount);
    }

    /**
     * @return Collection<Restaurant>
     */
    private function getAvailableRestaurants($guestCount, $reservationTime, $endTime): Collection
    {
        return Restaurant::available()
            ->where('region', session('region', 'miami'))
            ->with(['schedules' => function ($query) use ($guestCount, $reservationTime, $endTime) {
                $query->where('booking_date', $this->form->getState()['date'])
                    ->where('party_size', $guestCount)
                    ->where('start_time', '>=', $reservationTime)
                    ->where('start_time', '<=', $endTime);
            }])->get();
    }

    #[On('region-changed')]
    public function regionChanged(): void
    {
        $region = Region::query()->find(session('region', 'miami'));

        $this->timezone = $region->timezone;
        $this->currency = $region->currency;

        $this->restaurants = null;
        $this->form->fill();
    }

    /**
     * @throws Exception
     *
     * @todo: Extract the create booking from ReservationHub to a service class and reuse here
     */
    public function createBooking($scheduleTemplateId, $date): void
    {
        $this->redirectRoute('filament.admin.pages.concierge.reservation-hub', [
            'scheduleTemplateId' => $scheduleTemplateId,
            'date' => $date,
        ]);
    }
}
