<?php

namespace App\Livewire;

use App\Events\SMSMessageSent;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class SMSInput extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.s-m-s-input';
    protected static bool $isLazy = false;

    public string $phoneNumber;

    public string $message;

    public bool $messageSent = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                PhoneInput::make('phoneNumber')
                    ->hiddenLabel()
                    ->required(),
            ]);
    }

    /**
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function send(): void
    {
        $twilio = new Client(config('twilio-notification-channel.account_sid'), config('twilio-notification-channel.auth_token'));
        $message = $twilio->messages->create(
            $this->phoneNumber,
            [
                'from' => config('twilio-notification-channel.from'),
                'body' => $this->message,
            ]
        );

        $this->dispatch('sms-sent', messageId: $message->sid);

        SMSMessageSent::dispatch($message);

        Notification::make()
            ->title('SMS Message Sent Successfully')
            ->success()
            ->send();
    }
}
