<?php

namespace App\Livewire\Vip;

use App\Actions\Booking\CreateBooking;
use App\Models\Region;
use App\Models\Venue;
use App\Services\ReservationService;
use App\Services\VipCodeService;
use App\Traits\ManagesBookingForms;
use Exception;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

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

    public ?Region $region = null;

    public string $code = '';

    public function mount(): void
    {
        if (blank($this->code)) {
            $this->redirect('/');
        }

        if (! $vipCode = app(VipCodeService::class)->findByCode($this->code)) {
            $this->redirect('/');
        }

        $region_id = $vipCode->concierge->user->region ?? config('app.default_region');

        $this->region = Region::query()->find($region_id);
        $this->timezone = $this->region->timezone;
        $this->currency = $this->region->currency;
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

    public function createBooking(int $scheduleTemplateId, ?string $date = null): void
    {
        $this->loadVenues();

        $data = $this->form->getState();
        $data['date'] = $date ?? $data['date'];

        $vipCode = app(VipCodeService::class)->findByCode($this->code);
        try {
            $result = CreateBooking::run(
                scheduleTemplateId: $scheduleTemplateId,
                data: $data,
                timezone: $this->region->timezone,
                currency: $this->region->currency,
                vipCode: $vipCode);

            activity()
                ->performedOn($result->booking)
                ->withProperties([
                    'booking_id' => $result->booking->id,
                    'vip_code' => $this->code,
                    'schedule_template_id' => $scheduleTemplateId,
                    'booking_date' => $data['date'],
                    'guest_count' => $data['guest_count'],
                    'reservation_time' => $data['reservation_time'],
                ])
                ->log('VIP booking created');

            $this->redirect($result->bookingVipUrl);
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
