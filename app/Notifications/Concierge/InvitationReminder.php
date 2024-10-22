<?php

namespace App\Notifications\Concierge;

use App\Constants\SmsTemplates;
use App\Data\SmsData;
use App\Models\Referral;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class InvitationReminder extends Notification
{
    use Queueable;

    protected string $shortURL;

    /**
     * Create a new notification instance.
     *
     * @throws ShortURLException
     */
    public function __construct(public Referral $referral)
    {
        $url = URL::temporarySignedRoute('concierge.invitation', now()->addDays(15), [
            'referral' => $this->referral,
        ]);

        $this->shortURL = ShortURL::destinationUrl($url)->make()->default_short_url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable->phone) {
            return [SmsNotificationChannel::class];
        }

        return ['mail'];
    }

    public function toSms(Referral $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'concierge_reminder',
            templateData: [
                'first_name' => $this->referral->first_name,
                'referrer' => $this->referral->referrer->name,
                'url' => $this->shortURL,
            ]
        );
    }

    public function toMail($notifiable): MailMessage
    {
        $message = SmsTemplates::TEMPLATES['concierge_reminder'];
        $message = str_replace('{first_name}', $this->referral->first_name, $message);
        $message = str_replace('{referrer}', $this->referral->referrer->name, $message);
        $message = str_replace('{url}', $this->shortURL, $message);

        return (new MailMessage)
            ->subject('Reminder: Complete Your Concierge Account Setup')
            ->greeting('Reminder: Complete Your Concierge Account Setup')
            ->line($message)
            ->action('Secure Your Account', $this->shortURL);
    }
}
