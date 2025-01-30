<?php

namespace App\Notifications;

use App\Models\User;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WelcomeVenueManager extends Notification implements ShouldQueue
{
    use Queueable;

    private string $setPasswordUrl;

    /**
     * @throws ShortURLException
     */
    public function __construct(
        protected User $user,
        protected array $venues
    ) {
        $this->setPasswordUrl = $this->getSetPasswordUrl();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to PRIMA')
            ->greeting('Hello '.$this->user->first_name)
            ->line('Welcome to PRIMA! Your account has been created.')
            ->line('You are managing the following venues:')
            ->lines(collect($this->venues)->map(fn ($venue) => "- {$venue['name']}"))
            ->action('Set Your Password', $this->setPasswordUrl)
            ->line('This password setup link will expire in 7 days.')
            ->line('Thank you for using our platform!');
    }

    protected function getSetPasswordUrl(): string
    {
        $token = encrypt($this->user->id);
        $url = URL::signedRoute('password.create', ['token' => $token], now()->addDays(7));

        return ShortURL::destinationUrl($url)->make()->default_short_url;
    }
}
