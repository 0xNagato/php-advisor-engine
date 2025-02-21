<?php

namespace App\Notifications\Booking;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
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
        $createdAt = Carbon::parse($this->booking->created_at)->setTimezone('America/New_York')->format('l, F j, Y g:ia T');
        $bookingDate = Carbon::parse($this->booking->booking_at)->format('l, F j, Y');
        $bookingTime = Carbon::parse($this->booking->booking_at)->format('g:ia');

        $linkToViewBooking = ViewBooking::getUrl([
            'record' => $this->booking->id,
        ]);

        $totalFee = money($this->booking->total_fee, $this->booking->currency);

        return (new MailMessage)
            ->subject("New Booking Confirmation - {$this->booking->venue->name}")
            ->greeting('New Booking Confirmation')
            ->line("A new booking has been confirmed at {$this->booking->venue->name}.")
            ->line('**Booking Details:**')
            ->line("Created: {$createdAt}")
            ->line("Date: {$bookingDate}")
            ->line("Time: {$bookingTime}")
            ->line("Party Size: {$this->booking->guest_count}")
            ->line("Customer: {$this->booking->guest_first_name} {$this->booking->guest_last_name}")
            ->line("Total Fee: {$totalFee}")
            ->line("Source: {$this->booking->source}")
            ->line("Device: {$this->booking->device}")
            ->line('')
            ->line('**Confirmation Link:**')
            ->action('View Confirmation', $this->confirmationUrl)
            ->line('')
            ->line("[View Booking Details]({$linkToViewBooking})");
    }
}
