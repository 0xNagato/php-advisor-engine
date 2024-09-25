<?php

namespace App\Livewire;

use App\Services\SmsService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class SMSInput extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.s-m-s-input';

    protected static ?string $pollingInterval = null;

    public string $phoneNumber;

    public string $message;

    public bool $messageSent = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                PhoneInput::make('phoneNumber')
                    ->hiddenLabel()
                    ->onlyCountries(config('app.countries'))
                    ->displayNumberFormat(PhoneInputNumberType::E164)
                    ->disallowDropdown()
                    ->validateFor(
                        country: config('app.countries'),
                        type: PhoneNumberType::MOBILE,
                        lenient: true,
                    )
                    ->required(),
            ]);
    }

    public function send(): void
    {
        app(SmsService::class)->sendMessage(
            $this->phoneNumber,
            $this->message
        );

        $this->dispatch('sms-sent');

        Notification::make()
            ->title('SMS Message Sent Successfully')
            ->success()
            ->send();
    }
}
