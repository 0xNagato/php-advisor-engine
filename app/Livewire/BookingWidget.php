<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Restaurant;
use App\Models\Schedule;
use chillerlan\QRCode\QRCode;
use DateTime;
use Exception;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Session;
use Livewire\Features\SupportRedirects\Redirector;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class BookingWidget extends Widget
{
    protected static string $view = 'filament.widgets.booking-widget';

    /**
     * @var Collection<Restaurant>|null
     */
    public ?Collection $restaurants;


    public ?Restaurant $selectedRestaurant;

    public int|string|null $selectedRestaurantId;

    public ?Schedule $selectedSchedule;

    public int|string|null $selectedScheduleId;

    public ?int $guestCount;

    #[Session]
    public ?string $qrCode;

    #[Session]
    public ?string $bookingUrl;

    #[Session]
    public ?Booking $booking;

    public string $selectedDate;

    public bool $isLoading = false;

    public bool $paymentSuccess = false;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->restaurants = Restaurant::openToday()->get();
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function updatedSelectedRestaurantId($value): void
    {
        $this->selectedRestaurant = $this->restaurants->find($value);
    }

    public function updatedSelectedScheduleId($value): void
    {
        $this->selectedSchedule = Schedule::find($value);
    }

    public function updatedGuestCount(): void
    {
    }

    /**
     * @throws Exception
     */
    public function createBooking(): void
    {
        $this->booking = Booking::create([
            'schedule_id' => $this->selectedScheduleId,
            'guest_count' => $this->guestCount,
            'concierge_id' => auth()->user()->concierge->id,
            'status' => BookingStatus::PENDING,
            'booking_at' => $this->selectedSchedule->start_time->setDateFrom(new DateTime($this->selectedDate)),
        ]);

        $this->bookingUrl = route('bookings.create', ['token' => $this->booking->uuid]);

        $this->qrCode = (new QRCode())->render($this->bookingUrl);
    }

    /**
     * @throws ApiErrorException
     */
    public function completeBooking($form): Redirector
    {
        Stripe::setApiKey(config('cashier.secret'));

        $stripeCustomer = Customer::create([
            'name' => $form['first_name'] . ' ' . $form['last_name'],
            'phone' => $form['phone'],
            'source' => $form['token'],
        ]);

        $stripeCharge = Charge::create([
            'amount' => $this->booking->total_fee,
            'currency' => 'usd',
            'customer' => $stripeCustomer->id,
            'description' => 'Booking for ' . $this->booking->schedule->restaurant->restaurant_name,
        ]);

        $this->booking->update([
            'status' => BookingStatus::CONFIRMED,
            'stripe_charge' => $stripeCharge->toArray(),
            'stripe_charge_id' => $stripeCharge->id,
        ]);

        $this->isLoading = false;
        $this->paymentSuccess = true;

        return redirect()->route('filament.admin.resources.bookings.view', ['record' => $this->booking->id]);

    }

    public function cancelBooking(): void
    {
        $this->booking->update(['status' => 'cancelled']);
        $this->booking = null;
        $this->qrCode = null;
        $this->bookingUrl = null;
    }
}
