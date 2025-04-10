<?php

namespace App\Notifications\User;

use App\Data\SmsData;
use App\Models\User;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendTwoFactorCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $code,
        public string $channel = 'sms'
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return [
            $this->channel === 'sms' ? SmsNotificationChannel::class : 'mail',
        ];
    }

    public function toSms(User $notifiable): SMSData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'two_factor_code',
            templateData: [
                'code' => $this->code,
            ]
        );
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->from('prima@primavip.co', 'PRIMA VIP')
            ->subject('Your Two-Factor Authentication Code')
            ->greeting('Your 2FA login code for PRIMA is:')
            ->line("**{$this->code}**")
            ->line('Do not share this code with anyone. If you did not request this, please contact our support team immediately.')
            ->salutation('Thank you, PRIMA VIP Team');
    }
}
