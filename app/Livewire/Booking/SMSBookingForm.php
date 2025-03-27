<?php

/** @noinspection ALL */

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Notifications\Booking\SendCustomerBookingPaymentForm;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * @property Form $form
 */
class SMSBookingForm extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.booking.s-m-s-booking-form';

    protected static ?string $pollingInterval = null;

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
                ->displayNumberFormat(PhoneInputNumberType::E164)
                ->disallowDropdown()
                ->validateFor(
                    country: config('app.countries'),
                )
                ->columnSpan(2)
                ->required(),
            TextInput::make('email')
                ->hiddenLabel()
                ->email()
                ->placeholder('Email Address (optional)')
                ->autocomplete(false)
                ->columnSpan(2),
            Textarea::make('notes')
                ->hiddenLabel()
                ->placeholder('Notes/Special Requests (optional)')
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
        if (! config('app.bookings_enabled')) {
            $this->dispatch('open-modal', id: 'bookings-disabled-modal');

            return;
        }

        $data = $this->form->getState();

        $this->booking->update([
            'guest_first_name' => $data['first_name'],
            'guest_last_name' => $data['last_name'],
            'guest_email' => $data['email'],
            'guest_phone' => $data['phone'],
            'notes' => $data['notes'],
        ]);

        $shortUrl = ShortURL::destinationUrl($this->bookingUrl)->make()->default_short_url;

        $this->booking->notify(new SendCustomerBookingPaymentForm(url: $shortUrl));

        $this->SMSSent = true;
        $this->dispatch('sms-sent');

        Notification::make()
            ->title('SMS Message Sent Successfully')
            ->success()
            ->send();
    }
}
