<?php

namespace App\Filament\Pages\Concierge;

use App\Models\Region;
use App\Models\Venue;
use App\Services\ReservationService;
use App\Traits\ManagesBookingForms;
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

    protected static ?int $navigationSort = -4;

    public ?array $data = null;

    public string $currency;

    public ?string $endTimeForQuery = null;

    /**
     * @var Collection<Venue>|null
     */
    public ?Collection $venues = null;

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
        // $this->venues = Venue::available()->with(['schedules' => function ($query) {
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

    public function conciergePayout(Venue $venue): int
    {
        return ($venue->non_prime_fee_per_head * $this->data['guest_count']) * 90;
    }

    public function updatedData($data, $key): void
    {
        if ($key === 'guest_count' && blank($data)) {
            $this->data['guest_count'] = 2;
        }

        if (isset($this->data['reservation_time'], $this->data['date'], $this->data['guest_count'])) {
            $reservation = new ReservationService(
                date: $this->data['date'],
                guestCount: $this->data['guest_count'],
                reservationTime: $this->data['reservation_time'],
            );

            $this->timeslotHeaders = $reservation->getTimeslotHeaders();
            $this->venues = $reservation->getAvailableVenues();
        }
    }

    #[On('region-changed')]
    public function regionChanged(): void
    {
        $region = Region::query()->find(session('region', 'miami'));

        $this->timezone = $region->timezone;
        $this->currency = $region->currency;

        $this->venues = null;
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
