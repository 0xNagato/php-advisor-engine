<?php

namespace App\Livewire\Vip;

use App\Actions\Booking\CreateBooking;
use App\Models\Region;
use App\Models\Venue;
use App\Services\ReservationService;
use App\Traits\ManagesBookingForms;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Exception;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;

/**
 * @property Form $form
 */
class AvailabilityCalendar extends Page
{
    use ManagesBookingForms;

    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.vip.availability';

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
    }

    public function form(Form $form): Form
    {
        return $form->schema([...$this->commonFormComponents()])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('data');
    }

    public function updatedData($data, $key): void
    {
        if ($key === 'guest_count' && blank($data)) {
            $this->data['guest_count'] = 2;
        }

        $this->loadVenues();
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

    public function createBooking(int $scheduleTemplateId, ?string $date = null): void
    {
        $this->loadVenues();
        $region = Region::query()->find(session('region', 'miami'));
        $data = $this->form->getState();
        $data['date'] = $date ?? $data['date'];

        try {
            $result = CreateBooking::run($scheduleTemplateId, $data, $region->timezone, $region->currency, true);
            $vipUrl = ShortURL::destinationUrl(
                route('booking.checkout', [
                    'booking' => $result->booking->uuid,
                    'r' => 'vip',
                ])
            )->make();
            $this->redirect($vipUrl->default_short_url);
        } catch (Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function loadVenues(): void
    {
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
}
