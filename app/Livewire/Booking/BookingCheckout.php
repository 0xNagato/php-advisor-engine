<?php

namespace App\Livewire\Booking;

use App\Actions\Booking\CheckCustomerHasNonPrimeBooking;
use App\Actions\Booking\CompleteBooking;
use App\Enums\BookingStatus;
use App\Mail\CustomerInvoice;
use App\Models\Booking;
use App\Notifications\MultipleNonPrimeBookingAttemptNotification;
use App\Traits\FormatsPhoneNumber;
use Exception;
use Filament\Notifications\Notification as FilamentNotification;
use Ijpatricio\Mingle\Concerns\InteractsWithMingles;
use Ijpatricio\Mingle\Contracts\HasMingles;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Url;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class BookingCheckout extends Component implements HasMingles
{
    use FormatsPhoneNumber;
    use InteractsWithMingles;

    const int TIME_LIMIT_IN_MINUTES = 15;

    public Booking $booking;

    public ?Booking $existingBooking = null;

    #[Url]
    public $r = '';

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;

        if ($this->booking->vip_code_id) {
            $this->booking->load('vipCode');
        }

        if ($this->booking->clicked_at === null) {
            $this->booking->update(['clicked_at' => now()]);
        }

        if ($this->booking->status === BookingStatus::PENDING) {
            $this->booking->update(['status' => BookingStatus::GUEST_ON_PAGE]);
        }
    }

    public function component(): string
    {
        return 'resources/js/Booking/BookingCheckout/index.js';
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent(): string
    {
        $stripe = app(StripeClient::class);

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $this->booking->total_with_tax_in_cents,
            'currency' => $this->booking->currency,
            'payment_method_types' => ['card', 'link'],
        ]);

        return $paymentIntent->client_secret;
    }

    public function completeBooking(string $paymentIntentId, array $formData): array
    {
        return CompleteBooking::run($this->booking, $paymentIntentId, array_merge($formData, ['r' => $this->r]));
    }

    public function mingleData(): array
    {
        $expiresAt = $this->booking->created_at->addMinutes(self::TIME_LIMIT_IN_MINUTES);

        return [
            'status' => $this->booking->status,
            'stripeKey' => config('services.stripe.key'),
            'expiresAt' => $expiresAt,
            'formData' => [
                'firstName' => $this->booking->guest_first_name ?? '',
                'lastName' => $this->booking->guest_last_name ?? '',
                'email' => $this->booking->guest_email ?? '',
                'phone' => $this->booking->guest_phone ?? '',
                'notes' => $this->booking->notes ?? '',
            ],
            'vipCode' => $this->booking->vipCode?->code,
            'allowedPaymentMethods' => ['card', 'link'],
            'totalWithTaxesInCents' => (int) $this->booking->total_with_tax_in_cents,
            'isOmakase' => $this->booking->venue->is_omakase,
            'omakaseDetails' => $this->booking->venue->omakase_details,
            'minimumSpendPerGuest' => $this->booking->schedule->minimum_spend_per_guest ?? 0,
            'venueName' => $this->booking->venue->name,
        ];
    }

    public function emailInvoice(): void
    {
        $invoicePath = $this->booking->invoice_path;

        $mailable = new CustomerInvoice($this->booking);
        $mailable->attachFromStorageDisk('do', $invoicePath)
            ->from('welcome@primavip.co', 'PRIMA');

        Mail::to($this->booking->guest_email)
            ->send($mailable);

        FilamentNotification::make()
            ->title('Invoice sent to '.$this->booking->guest_email)
            ->success()
            ->send();
    }

    public function getDownloadInvoiceUrl(): string
    {
        return route('customer.invoice.download', ['uuid' => $this->booking->uuid]);
    }

    public function checkForExistingBooking(array $formData): ?array
    {
        if (! $this->booking->is_prime) {
            $hasExistingBooking = CheckCustomerHasNonPrimeBooking::run(
                $formData['phone'],
                $this->booking->booking_at->format('Y-m-d'),
                $this->booking->venue->timezone
            );

            if ($hasExistingBooking) {
                $this->existingBooking = Booking::query()
                    ->where('guest_phone', $formData['phone'])
                    ->where('is_prime', false)
                    ->whereDate('booking_at', $this->booking->booking_at->format('Y-m-d'))
                    ->first();

                return [
                    'error' => 'multiple_booking',
                    'message' => 'You already have a non-prime reservation for this day.',
                ];
            }
        }

        return null;
    }

    public function submitCustomerMessage(string $message, string $phone): array
    {
        $existingBooking = CheckCustomerHasNonPrimeBooking::run(
            $phone,
            $this->booking->booking_at->format('Y-m-d'),
            $this->booking->venue->timezone
        );

        if (! $existingBooking) {
            return [
                'success' => false,
                'message' => 'Unable to process request: No existing booking found.',
            ];
        }

        $notification = new MultipleNonPrimeBookingAttemptNotification(
            $existingBooking,
            $this->booking,
            $message
        );

        Notification::route('mail', [
            'prima@primavip.co' => 'PRIMA Team',
            'kevin@primavip.co' => 'Kevin',
            'alex@primavip.co' => 'Alex',
            'andru.weir@gmail.com' => 'Andru',
        ])->notify($notification);

        return [
            'success' => true,
            'message' => 'Thank you for letting us know. Our team will review your request.',
        ];
    }

    /**
     * Format a phone number using the server-side formatter
     *
     * @param  string  $phoneNumber  The phone number to format
     */
    public function formatPhoneNumber(string $phoneNumber): array
    {
        try {
            $formattedNumber = $this->getInternationalFormattedPhoneNumber($phoneNumber);

            // If the formatter returned an empty string, the number is invalid
            if (blank($formattedNumber)) {
                return [
                    'success' => false,
                    'message' => 'Please enter a valid phone number that can receive SMS',
                ];
            }

            return [
                'success' => true,
                'formattedNumber' => $formattedNumber,
            ];
        } catch (Exception) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format. Please enter a valid phone number.',
            ];
        }
    }
}
