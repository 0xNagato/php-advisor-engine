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
        protected array $venues,
        protected bool $isExistingManager = false
    ) {
        $this->setPasswordUrl = $this->getSetPasswordUrl();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->isExistingManager ? 'New Venues Added to Your PRIMA Account' : 'Welcome to PRIMA')
            ->greeting('Hello '.$this->user->first_name);

        if ($this->isExistingManager) {
            // For existing venue managers, just confirm the new venues
            $mailMessage
                ->line('Your new venues have been added to your PRIMA account.')
                ->line('The following venues are now available in your dashboard:')
                ->lines(collect($this->venues)->map(fn ($venue) => "- {$venue['name']}"))
                ->action('Go to Dashboard', route('filament.admin.pages.venue-manager-dashboard'))
                ->line('Thank you for expanding with PRIMA!');
        } else {
            // For new venue managers, include password setup
            $mailMessage
                ->line('Welcome to PRIMA! Your account has been created.')
                ->line('You are managing the following venues:')
                ->lines(collect($this->venues)->map(fn ($venue) => "- {$venue['name']}"))
                ->action('Set Your Password', $this->setPasswordUrl)
                ->line('This password setup link will expire in 7 days.')
                ->line('Thank you for using our platform!');
        }

        return $mailMessage;
    }

    protected function getSetPasswordUrl(): string
    {
        $token = encrypt($this->user->id);
        $url = URL::signedRoute('password.create', ['token' => $token], now()->addDays(7));

        return ShortURL::destinationUrl($url)->make()->default_short_url;
    }
}
