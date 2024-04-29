<?php /** @noinspection ALL */

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Services\SmsService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * @property Form $form
 */
class SMSBookingForm extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.bookings.s-m-s-booking-form';

    public ?array $data = [];
    public bool $SMSSent = false;
    public Booking $booking;
    public string $bookingUrl;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('first_name')
                ->hiddenLabel()
                ->placeholder('First Name')
                ->required(),
            TextInput::make('last_name')
                ->hiddenLabel()
                ->placeholder('Last Name')
                ->required(),
            PhoneInput::make('phone')
                ->hiddenLabel()
                ->onlyCountries(config('app.countries'))
                ->validateFor(
                    country: config('app.countries'),
                    type: PhoneNumberType::MOBILE,
                    lenient: true,
                )
                ->columnSpan(2)
                ->required(),
            TextInput::make('email')
                ->hiddenLabel()
                ->email()
                ->placeholder('Email Address (optional)')
                ->autocomplete(false)
                ->columnSpan(2),
        ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('data');
    }

    public function handleSubmit(): void
    {
        $data = $this->form->getState();
        $message = "Your reservation at {$this->booking->restaurant->restaurant_name} is pending. Please click $this->bookingUrl to secure your booking within the next 5 minutes.";

        $this->booking->update([
            'guest_first_name' => $data['first_name'],
            'guest_last_name' => $data['last_name'],
            'guest_email' => $data['email'],
            'guest_phone' => $data['phone'],
        ]);

        app(SmsService::class)->sendMessage($data['phone'], $message);

        $this->SMSSent = true;
        $this->dispatch('sms-sent');

        Notification::make()
            ->title('SMS Message Sent Successfully')
            ->success()
            ->send();
    }
}
