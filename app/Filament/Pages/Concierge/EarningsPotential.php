<?php

namespace App\Filament\Pages\Concierge;

use App\Constants\BookingPercentages;
use App\Models\Concierge;
use App\Models\Region;
use App\Models\ScheduleWithBooking;
use App\Models\Venue;
use App\Services\ReservationService;
use App\Traits\ManagesBookingForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;

/**
 * @property Form $form
 */
class EarningsPotential extends Page
{
    use ManagesBookingForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.concierge.earnings-potential';

    protected static ?int $navigationSort = -1;

    public ?array $data = null;

    public string $currency = '';

    public ?string $endTimeForQuery = null;

    /**
     * @var Collection<int, Venue>|null
     */
    public ?Collection $venues = null;

    /**
     * @var array<int, string>
     */
    public array $timeslotHeaders = [];

    public function mount(): void
    {
        $region = Region::query()->find(session('region', 'miami'));
        $this->timezone = $region?->timezone;
        $this->currency = $region?->currency;
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        if (session()?->exists('simpleMode')) {
            return ! session('simpleMode');
        }

        return auth()->user()?->hasActiveRole('concierge') ?? false;
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

    public function conciergePayout(Venue $venue, ScheduleWithBooking $schedule, bool $isPrime): int
    {
        $concierge = Concierge::query()->where('user_id', auth()->id())->first();
        if (! $concierge) {
            return 0;
        }

        $guestCount = $this->data['guest_count'];

        if ($isPrime) {
            $totalFee = $venue->booking_fee;
            if ($guestCount > 2) {
                $totalFee += $venue->increment_fee * ($guestCount - 2);
            }

            return (int) ($totalFee * ($concierge->payout_percentage / 100) * 100);
        }

        $non_prime_fee_per_head = $venue->non_prime_fee_per_head;
        if ($schedule->timeSlots->count()) {
            $override = $schedule->timeSlots->first();
            $non_prime_fee_per_head = $override->price_per_head;
        }
        $totalFee = $non_prime_fee_per_head * $guestCount;

        return (int) ($totalFee * (1 - (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100)) * 100);
    }

    public function customerSpending(Venue $venue, bool $isPrime): int
    {
        $guestCount = $this->data['guest_count'];

        if ($isPrime) {
            $totalFee = $venue->booking_fee;
            if ($guestCount > 2) {
                $totalFee += $venue->increment_fee * ($guestCount - 2);
            }
        } else {
            $totalFee = $venue->non_prime_fee_per_head * $guestCount;
        }

        return (int) ($totalFee * 100);
    }

    public function updatedData(mixed $data, string $key): void
    {
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
        $region = Region::query()->find(session('region', 'miami'));

        $this->timezone = $region?->timezone;
        $this->currency = $region?->currency;

        $this->venues = null;
        $this->form->fill();
    }
}
