<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class MultipleNonPrimeBookingAttemptNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Booking $existingBooking,
        protected Booking $attemptedBooking,
        protected string $customerMessage
    ) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Multiple Non-Prime Booking Attempt')
            ->greeting('Multiple Non-Prime Booking Attempt Detected')
            ->line('A customer attempted to book more than one non-prime reservation for the same day.')
            ->line('Customer Details:')
            ->line("Name: {$this->existingBooking->guest_name}")
            ->line("Phone: {$this->existingBooking->guest_phone}")
            ->line("Email: {$this->existingBooking->guest_email}")
            ->line('')
            ->line('Existing Booking:')
            ->line("Venue: {$this->existingBooking->venue->name}")
            ->line("Date: {$this->existingBooking->booking_at->format('M d, Y')}")
            ->line("Time: {$this->existingBooking->booking_at->format('h:i A')}")
            ->line('')
            ->line('Attempted Additional Booking:')
            ->line("Venue: {$this->attemptedBooking->venue->name}")
            ->line("Date: {$this->attemptedBooking->booking_at->format('M d, Y')}")
            ->line("Time: {$this->attemptedBooking->booking_at->format('h:i A')}")
            ->line('')
            ->line('Customer Message:')
            ->line($this->customerMessage)
            ->attachData(
                Storage::disk('do')->get($this->existingBooking->invoice_path),
                'existing_booking_invoice.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}
