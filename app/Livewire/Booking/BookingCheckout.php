<?php

namespace App\Livewire\Booking;

use App\Actions\Booking\CompleteBooking;
use App\Enums\BookingStatus;
use App\Mail\CustomerInvoice;
use App\Models\Booking;
use App\Traits\FormatsPhoneNumber;
use Filament\Notifications\Notification;
use Ijpatricio\Mingle\Concerns\InteractsWithMingles;
use Ijpatricio\Mingle\Contracts\HasMingles;
use Illuminate\Support\Facades\Mail;
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
        $stripe = new StripeClient(config('services.stripe.secret'));

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $this->booking->total_with_tax_in_cents,
            'currency' => $this->booking->currency,
            'payment_method_types' => ['card', 'link'],
        ]);

        // $this->booking->update(['stripe_payment_intent_id' => $paymentIntent->id]);

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

        Notification::make()
            ->title('Invoice sent to '.$this->booking->guest_email)
            ->success()
            ->send();
    }

    public function getDownloadInvoiceUrl(): string
    {
        return route('customer.invoice.download', ['uuid' => $this->booking->uuid]);
    }
}
