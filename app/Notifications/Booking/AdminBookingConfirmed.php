<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminBookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly string $confirmationUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $bookingDate = Carbon::parse($this->booking->booking_at)->format('l, F j, Y');
        $bookingTime = Carbon::parse($this->booking->booking_at)->format('g:ia');

        return (new MailMessage)
            ->subject("New Booking Confirmation - {$this->booking->venue->name}")
            ->greeting('New Booking Confirmation')
            ->line("A new booking has been confirmed at {$this->booking->venue->name}.")
            ->line('**Booking Details:**')
            ->line("Date: {$bookingDate}")
            ->line("Time: {$bookingTime}")
            ->line("Party Size: {$this->booking->guest_count}")
            ->line("Customer: {$this->booking->guest_first_name} {$this->booking->guest_last_name}")
            ->line('')
            ->line('**Confirmation Link:**')
            ->action('View Confirmation', $this->confirmationUrl);
    }
}
