@php
    use App\Enums\BookingStatus;
    use App\Filament\Resources\ConciergeResource;
    use App\Filament\Resources\PartnerResource;
    use App\Filament\Resources\VenueResource;
    use libphonenumber\PhoneNumberFormat;
@endphp
<div @class([
    'relative',
    'py-4' => !$download,
    'mt-4' => $download,
    'px-4' => isset($customerInvoice),
])>
    @if (!$download && isset($customerInvoice))
        <div class="flex max-w-3xl mb-4 gap-x-2 lg:mx-auto">
            <x-filament::button color="indigo" class="w-1/2" size="sm" icon="gmdi-email-o" wire:click="showEmailForm">
                Email Invoice
            </x-filament::button>
            <x-filament::button color="indigo" class="w-1/2" size="sm" icon="gmdi-file-download-o" tag="a"
                :href="route('customer.invoice.download', ['uuid' => $booking->uuid])">
                Download PDF
            </x-filament::button>
        </div>

        @if (isset($emailOpen) && $emailOpen)
            <form wire:submit="emailInvoice" class="max-w-3xl p-4 mx-auto my-4 bg-gray-100 border rounded-lg">
                {{ $this->form }}
                <button type="submit"
                    class="w-full px-4 py-2 mt-4 text-xs font-semibold text-white bg-indigo-600 rounded-lg sm:text-xs">
                    Send Email
                </button>
            </form>
        @endif
    @endif

    <div
        class="bg-white rounded-xl shadow sm:max-w-3xl lg:mx-auto invoice-container flex flex-col @if ($download) min-h-[10in] @endif">
        <div class="relative overflow-hidden bg-indigo-800 min-h-32 rounded-t-xl">
            @if (!isset($customerInvoice) && !$download)
                <div class="flex justify-between">
                    <button class="p-4 font-semibold text-white" onclick="window.history.back();">
                        &#x276E;&nbsp Back
                    </button>
                </div>
            @endif
            <!-- SVG Background Element -->
            <figure class="absolute inset-x-0 bottom-0 -mb-px ">
                <svg preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                    viewBox="0 0 1920 100.1">
                    <path fill="currentColor" class="fill-white" d="M0,0c0,0,934.4,93.4,1920,0v100.1H0L0,0z"></path>
                </svg>
            </figure>
            <!-- End SVG Background Element -->
        </div>

        <div class="relative z-10 -mt-12">
            <!-- Icon -->
            <span
                class="mx-auto flex justify-center items-center size-[62px] rounded-full border border-gray-200 bg-white text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
                <x-polaris-receipt-icon class="w-10 h-10 text-gray-700" />
            </span>
            <!-- End Icon -->
        </div>

        <!-- Body -->
        <div class="p-4 sm:p-7">
            <div class="text-center">
                <h3 class="text-xl font-bold leading-5 tracking-tight text-gray-950">
                    PRIMA
                </h3>
                <p class="text-xs text-gray-500 sm:text-xs">
                    Invoice #{{ $booking->id }}
                </p>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-2 gap-5 mt-5 sm:mt-10 sm:grid-cols-4">
                <div>
                    <span class="block text-xs text-gray-500 uppercase">Customer:</span>
                    <div class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $booking->guest_name }}
                        <br />
                        {{ $booking->guest_phone }}
                        @if ($booking->guest_email)
                            <br />
                            {{ $booking->guest_email }}
                        @endif
                    </div>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Payment Method:</span>
                    <div class="flex items-center gap-x-2">
                        @if ($booking->prime_time)
                            @if ($booking->stripe_charge && $booking->stripe_charge->paymentMethodDetails->card)
                                @if ($booking->stripe_charge->paymentMethodDetails->card->brand === 'visa')
                                    <x-fab-cc-visa class="w-6 h-6" />
                                @elseif ($booking->stripe_charge->paymentMethodDetails->card->brand === 'mastercard')
                                    <x-fab-cc-mastercard class="w-6 h-6" />
                                @elseif ($booking->stripe_charge->paymentMethodDetails->card->brand === 'amex')
                                    <x-fab-cc-amex class="w-6 h-6" />
                                @endif
                                <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                                    ••••{{ $booking->stripe_charge->paymentMethodDetails->card->last4 }}
                                </span>
                            @elseif ($booking->stripe_charge)
                                <x-fab-stripe class="w-6 h-6" />
                                <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                                    Stripe
                                </span>
                            @else
                                <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                                    Credit Card
                                </span>
                            @endif
                        @else
                            <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                                Non-Prime
                                @isset($booking->meta['non_prime_incentive'])
                                    <span class="text-xs text-gray-500">
                                        ({{ money($booking->meta['non_prime_incentive']['fee_per_head'] * 100 ?? 0, $booking->currency) }}/guest)
                                    </span>
                                @endisset
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Created At:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $booking->created_at->setTimezone(auth()->user()?->timezone ?? 'America/New_York')->format('M d, Y g:i A') }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Booking Time:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $booking->booking_at->format('M d, Y g:i A') }}
                    </span>
                </div>

                <div>
                    <span
                        class="block text-xs text-gray-500 uppercase">{{ $booking->is_prime ? 'Date Paid' : 'Date Confirmed' }}:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $booking->confirmed_at?->setTimezone(auth()->user()?->timezone ?? 'America/New_York')->format('M d, Y g:i A') }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Concierge:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        @if ($booking->concierge && auth()->check() && auth()->user()->hasActiveRole('super_admin'))
                            {{ $this->viewConciergeAction }}
                        @elseif ($booking->concierge)
                            {{ $booking->concierge->user->name }}
                            @if ($booking->concierge->hotel_name)
                                ({{ $booking->concierge->hotel_name }})
                            @endif
                        @else
                            -
                        @endif
                    </span>
                </div>

                @if ($booking->status === BookingStatus::REFUNDED || $booking->status === BookingStatus::PARTIALLY_REFUNDED)
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Status:</span>
                        <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                            {{ $booking->status->label() }}
                            @if ($booking->status === BookingStatus::PARTIALLY_REFUNDED)
                                ({{ money($booking->refund_data['amount'], $booking->currency) }} refunded)
                            @endif
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Refund Reason:</span>
                        <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                            {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $booking->refund_data['reason'])) }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Refund At:</span>
                        <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                            {{ $booking->refunded_at->format('M d, Y g:i A') }}
                        </span>
                    </div>

                    @if (auth()->check() && auth()->user()->hasActiveRole('super_admin'))
                        <div>
                            <span class="block text-xs text-gray-500 uppercase">Internal Notes:</span>
                            <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                                {{ $booking->refund_reason }}
                            </span>
                        </div>
                    @endif
                @else
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Status:</span>
                        <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                            {{ $booking->status->label() }}
                        </span>
                    </div>
                @endif
            </div>
            <!-- End Grid -->

            <div class="mt-5 sm:mt-10">
                @if ($booking->notes)
                    <h4 class="text-xs font-semibold text-gray-800 uppercase dark:text-gray-200">Notes</h4>
                    <p class="mb-3 text-xs text-gray-800 sm:text-xs dark:text-gray-200">{{ $booking->notes }}
                    </p>
                @endif

                <h4 class="text-xs font-semibold text-gray-800 uppercase dark:text-gray-200">Summary</h4>

                <ul class="flex flex-col mt-3 overflow-hidden border rounded-lg">
                    <li
                        class="inline-flex items-center px-4 py-3 text-xs text-gray-800 border-b sm:text-sm gap-x-2 last:border-b-0 dark:border-gray-700 dark:text-gray-200">
                        <div class="flex flex-col w-full">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-medium">
                                    {{ $booking->venue->name }} ({{ $booking->guest_count }} guests)
                                </span>
                                <span class="font-medium">
                                    {{ money($booking->total_fee, $booking->currency) }}
                                </span>
                            </div>
                            @if ($booking->is_prime)
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ money($booking->venue->booking_fee * 100, $booking->currency) }} for 2 guests
                                    @if ($booking->guest_count > 2)
                                        +
                                        {{ money($booking->venue->increment_fee * 100, $booking->currency) }}/additional
                                        guest
                                    @endif
                                </div>
                            @endif
                        </div>
                    </li>
                    @if ($booking->tax > 0)
                        <li
                            class="inline-flex items-center px-4 py-3 text-xs text-gray-800 border-b sm:text-sm last:border-b-0 dark:border-gray-700 dark:text-gray-200">
                            <div class="flex items-center justify-between w-full">
                                <span>{{ $region->tax_rate_term }} ({{ $booking->tax * 100 }}%)</span>
                                <span>
                                    {{ money($booking->tax_amount_in_cents, $booking->currency) }}
                                </span>
                            </div>
                        </li>
                    @endif
                    @if (in_array($booking->status, [BookingStatus::REFUNDED, BookingStatus::PARTIALLY_REFUNDED]))
                        <li
                            class="inline-flex items-center px-4 py-3 text-xs text-gray-800 border-b sm:text-sm last:border-b-0 dark:border-gray-700 dark:text-gray-200">
                            <div class="flex items-center justify-between w-full">
                                <span>{{ $booking->status->label() }}</span>
                                <span class="text-red-500">
                                    -{{ money($booking->total_refunded, $booking->currency) }}
                                </span>
                            </div>
                        </li>
                    @endif
                    @if (!in_array($booking->status, [BookingStatus::PENDING, BookingStatus::GUEST_ON_PAGE, BookingStatus::ABANDONED]))
                        <li
                            class="inline-flex items-center px-4 py-3 text-xs font-semibold text-gray-800 sm:text-sm bg-gray-50 dark:bg-slate-800 dark:text-gray-200">
                            <div class="flex items-center justify-between w-full">
                                <span>Amount Paid</span>
                                <span>
                                    @if (in_array($booking->status, [BookingStatus::REFUNDED, BookingStatus::PARTIALLY_REFUNDED]))
                                        {{ money($booking->final_total, $booking->currency) }}
                                    @else
                                        {{ money($booking->total_with_tax_in_cents, $booking->currency) }}
                                    @endif
                                </span>
                            </div>
                        </li>
                    @endif
                </ul>

                @if (isset($customerInvoice) || $download)
                    <div class="mt-4 font-semibold text-center">
                        @if ($booking->venue->is_omakase)
                            {{ $booking->venue->omakase_details }}
                        @else
                            Fees paid are for reservation only. Not applicable towards venue bill.
                        @endif
                    </div>
                @endif
            </div>

            @if (auth()->check() &&
                    auth()->user()->hasActiveRole('super_admin') &&
                    $booking->status !== BookingStatus::PENDING &&
                    $booking->status !== BookingStatus::GUEST_ON_PAGE &&
                    $booking->status !== BookingStatus::ABANDONED &&
                    $booking->status !== BookingStatus::CANCELLED)
                @php
                    $booking->load('earnings.user.venue', 'earnings.user.concierge', 'earnings.user.partner');
                @endphp
                <div class="mt-6">
                    <div class="flex flex-col gap-8 lg:flex-row">
                        <div class="w-full lg:w-1/2">
                            <livewire:booking.earnings-breakdown :booking="$booking" />
                        </div>
                        @if (!$booking->is_refunded)
                            <div class="w-full -mt-2 lg:w-1/2">
                                <livewire:payout-breakdown-chart :booking="$booking" />
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            @if (auth()->check() && auth()->user()->hasActiveRole('super_admin'))
                <x-filament::actions :actions="[
                    $this->resendInvoiceAction,
                    $this->refundBookingAction,
                    $this->convertToNonPrimeBookingAction,
                    $this->convertToPrimeBookingAction,
                    $this->abandonBookingAction,
                    $this->uncancelBookingAction,
                    $this->markAsNoShowAction,
                    $this->transferBookingAction,
                ]" class="w-full mt-4" />
            @endif

            @if ($this->canModifyBooking)
                <x-filament::actions :actions="[$this->cancelBookingAction, $this->modifyGuestInfoAction]" class="w-full mt-4" />

                <x-filament::button wire:click="$dispatch('open-modal', { id: 'modify-booking-{{ $booking->id }}' })"
                    class="w-full mt-3" icon="heroicon-m-pencil-square" :disabled="$booking->hasActiveModificationRequest()">
                    {{ $booking->hasActiveModificationRequest() ? 'Modification Request Pending' : 'Modify Booking' }}
                </x-filament::button>

                <x-filament::modal id="modify-booking-{{ $booking->id }}" width="md">
                    <x-slot name="heading">
                        Modify Booking
                    </x-slot>

                    <x-slot name="description">
                        <div class="text-sm text-gray-500">
                            We must confirm all reservations with the participating venue. Please submit any change
                            requests needed here. We will confirm the changes requested within 15-30 minutes and notify
                            both you and the customer.
                        </div>
                    </x-slot>

                    <div>
                        <livewire:booking.modify-non-prime-booking-widget :booking="$booking" :show-details="false" />
                    </div>
                </x-filament::modal>
            @endif
        </div>
        <!-- End Body -->
    </div>
    @if (!$download)
        <x-filament-actions::modals />
    @endif

</div>
