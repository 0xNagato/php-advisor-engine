<?php

namespace App\Filament\Pages\Concierge;

use App\Models\Booking;
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

    public ?Region $region = null;

    public function mount(): void
    {
        $region = auth()->user()->region;

        if (! $region) {
            $region = Region::default()->id;
            auth()->user()->update(['region' => $region]);
        }
        /** @var Region $this->region */
        $this->region = Region::query()->find($region);
        $this->timezone = $this->region?->timezone;
        $this->currency = $this->region?->currency;
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole(['concierge', 'partner']);
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
        // Check if user's region has changed and update accordingly
        $currentUserRegion = auth()->user()->region;
        if ($this->region?->id !== $currentUserRegion) {
            /** @var Region $this->region */
            $this->region = Region::query()->find($currentUserRegion);
            $this->timezone = $this->region?->timezone;
            $this->currency = $this->region?->currency;
        }

        if ($key === 'guest_count' && blank($data)) {
            $this->data['guest_count'] = 2;
        }

        if (isset($this->data['reservation_time'], $this->data['date'], $this->data['guest_count'])) {
            $reservation = new ReservationService(
                date: $this->data['date'],
                guestCount: $this->data['guest_count'],
                reservationTime: $this->data['reservation_time'],
                timeslotCount: $this->data['timeslot_count'] ?? 5,
                timeSlotOffset: 2
            );

            $this->venues = $reservation->getAvailableVenues();
            $this->timeslotHeaders = $reservation->getTimeslotHeaders();
        }
    }

    #[On('region-changed')]
    public function regionChanged(): void
    {
        $region = auth()->user()->region;

        /** @var Region $this->region */
        $this->region = Region::query()->find($region);

        $this->timezone = $this->region?->timezone;
        $this->currency = $this->region?->currency;

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
        // Clear any existing booking session
        session()->forget(['booking', 'qrCode', 'bookingUrl']);

        $this->redirectRoute('filament.admin.pages.concierge.reservation-hub', [
            'scheduleTemplateId' => $scheduleTemplateId,
            'date' => $date,
            'guestCount' => $this->data['guest_count'],
        ]);
    }
}
