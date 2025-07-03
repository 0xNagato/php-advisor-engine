@php
    use App\Enums\BookingStatus;
    use App\Filament\Resources\ConciergeResource;
    use App\Filament\Resources\PartnerResource;
    use App\Filament\Resources\VenueResource;
    use libphonenumber\PhoneNumberFormat;
@endphp
<div @class([
    'relative',
    'py-4',
    'px-4'
])>
    <div class="flex max-w-3xl mb-4 gap-x-2 lg:mx-auto">
        <x-filament::button color="indigo" class="w-1/2" size="sm" icon="gmdi-email-o" wire:click="showEmailForm">
            Email Invoice
        </x-filament::button>
        <x-filament::button color="indigo" class="w-1/2" size="sm" icon="gmdi-file-download-o" tag="a"
                            :href="route('customer.invoice.download', ['uuid' => $record->uuid])">
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

    <div
            class="bg-white rounded-xl shadow sm:max-w-3xl lg:mx-auto invoice-container flex flex-col">
        <div class="relative overflow-hidden bg-indigo-800 min-h-32 rounded-t-xl">
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
                    Invoice #{{ $record->id }}
                </p>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-2 gap-5 mt-5 sm:mt-10 sm:grid-cols-4">
                <div>
                    <span class="block text-xs text-gray-500 uppercase">Customer:</span>
                    <div class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $record->guest_name }}
                        <br />
                        {{ $record->guest_phone }}
                        @if ($record->guest_email)
                            <br />
                            {{ $record->guest_email }}
                        @endif
                    </div>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Payment Method:</span>
                    <div class="flex items-center gap-x-2">
                        @if ($record->prime_time)
                            @if ($record->stripe_charge && $record->stripe_charge->paymentMethodDetails->card)
                                @if ($record->stripe_charge->paymentMethodDetails->card->brand === 'visa')
                                    <x-fab-cc-visa class="w-6 h-6" />
                                @elseif ($record->stripe_charge->paymentMethodDetails->card->brand === 'mastercard')
                                    <x-fab-cc-mastercard class="w-6 h-6" />
                                @elseif ($record->stripe_charge->paymentMethodDetails->card->brand === 'amex')
                                    <x-fab-cc-amex class="w-6 h-6" />
                                @endif
                                <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                                    ••••{{ $record->stripe_charge->paymentMethodDetails->card->last4 }}
                                </span>
                            @elseif ($record->stripe_charge)
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
                                @if (isset($record->meta['non_prime_incentive']))
                                    <span class="text-xs text-gray-500">
                                        ({{ money($record->meta['non_prime_incentive']['fee_per_head'] * 100 ?? 0, $record->currency) }}/guest)
                                    </span>
                                @endif
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Created At:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $record->created_at->setTimezone(auth()->user()?->timezone ?? 'America/New_York')->format('M d, Y g:i A') }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Booking Time:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $record->booking_at->format('M d, Y g:i A') }}
                    </span>
                </div>

                <div>
                    <span
                            class="block text-xs text-gray-500 uppercase">{{ $record->is_prime ? 'Date Paid' : 'Date Confirmed' }}:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $record->confirmed_at?->setTimezone(auth()->user()?->timezone ?? 'America/New_York')->format('M d, Y g:i A') }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Concierge:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        @if ($record->concierge && auth()->check() && auth()->user()->hasActiveRole('super_admin'))
                            {{ $this->viewConciergeAction }}
                        @elseif ($record->concierge)
                            {{ $record->concierge->user->name }}
                            @if ($record->concierge->hotel_name)
                                ({{ $record->concierge->hotel_name }})
                            @endif
                        @else
                            -
                        @endif
                    </span>
                </div>

                @if ($record->status === BookingStatus::REFUNDED || $record->status === BookingStatus::PARTIALLY_REFUNDED)
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Status:</span>
                        <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                            {{ $record->status->label() }}
                            @if ($record->status === BookingStatus::PARTIALLY_REFUNDED)
                                ({{ money($record->refund_data['amount'], $record->currency) }} refunded)
                            @endif
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Refund Reason:</span>
                        <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                            {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $record->refund_data['reason'])) }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Refund At:</span>
                        <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                            {{ $record->refunded_at->format('M d, Y g:i A') }}
                        </span>
                    </div>

                    @if (auth()->check() && auth()->user()->hasActiveRole('super_admin'))
                        <div>
                            <span class="block text-xs text-gray-500 uppercase">Internal Notes:</span>
                            <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                                {{ $record->refund_reason }}
                            </span>
                        </div>
                    @endif
                @else
                    <div>
                        <span class="block text-xs text-gray-500 uppercase">Status:</span>
                        <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                            {{ $record->status->label() }}
                        </span>
                    </div>
                @endif
            </div>
            <!-- End Grid -->

            <div class="mt-5 sm:mt-10">
                @if ($record->notes)
                    <h4 class="text-xs font-semibold text-gray-800 uppercase dark:text-gray-200">Notes</h4>
                    <p class="mb-3 text-xs text-gray-800 sm:text-xs dark:text-gray-200">{{ $record->notes }}
                    </p>
                @endif

                <h4 class="text-xs font-semibold text-gray-800 uppercase dark:text-gray-200">Summary</h4>

                <ul class="flex flex-col mt-3 overflow-hidden border rounded-lg">
                    <li
                            class="inline-flex items-center px-4 py-3 text-xs text-gray-800 border-b sm:text-sm gap-x-2 last:border-b-0 dark:border-gray-700 dark:text-gray-200">
                        <div class="flex flex-col w-full">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-medium">
                                    {{ $record->venue->name }} ({{ $record->guest_count }} guests)
                                </span>
                                <span class="font-medium">
                                    {{ money($record->total_fee, $record->currency) }}
                                </span>
                            </div>
                            @if ($record->is_prime)
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ money($record->venue->booking_fee * 100, $record->currency) }} for 2 guests
                                    @if ($record->guest_count > 2)
                                        +
                                        {{ money($record->venue->increment_fee * 100, $record->currency) }}/additional
                                        guest
                                    @endif
                                </div>
                            @endif
                        </div>
                    </li>
                    @if ($record->tax > 0)
                        <li
                                class="inline-flex items-center px-4 py-3 text-xs text-gray-800 border-b sm:text-sm last:border-b-0 dark:border-gray-700 dark:text-gray-200">
                            <div class="flex items-center justify-between w-full">
                                <span>{{ $region->tax_rate_term }} ({{ $record->tax * 100 }}%)</span>
                                <span>
                                    {{ money($record->tax_amount_in_cents, $record->currency) }}
                                </span>
                            </div>
                        </li>
                    @endif
                    @if (in_array($record->status, [BookingStatus::REFUNDED, BookingStatus::PARTIALLY_REFUNDED]))
                        <li
                                class="inline-flex items-center px-4 py-3 text-xs text-gray-800 border-b sm:text-sm last:border-b-0 dark:border-gray-700 dark:text-gray-200">
                            <div class="flex items-center justify-between w-full">
                                <span>{{ $record->status->label() }}</span>
                                <span class="text-red-500">
                                    -{{ money($record->total_refunded, $record->currency) }}
                                </span>
                            </div>
                        </li>
                    @endif
                    @if (!in_array($record->status, [BookingStatus::PENDING, BookingStatus::GUEST_ON_PAGE, BookingStatus::ABANDONED]))
                        <li
                                class="inline-flex items-center px-4 py-3 text-xs font-semibold text-gray-800 sm:text-sm bg-gray-50 dark:bg-slate-800 dark:text-gray-200">
                            <div class="flex items-center justify-between w-full">
                                <span>Amount Paid</span>
                                <span>
                                    @if (in_array($record->status, [BookingStatus::REFUNDED, BookingStatus::PARTIALLY_REFUNDED]))
                                        {{ money($record->final_total, $record->currency) }}
                                    @else
                                        {{ money($record->total_with_tax_in_cents, $record->currency) }}
                                    @endif
                                </span>
                            </div>
                        </li>
                    @endif
                </ul>


                <div class="mt-4 font-semibold text-center">
                    @if ($record->venue->is_omakase)
                        {{ $record->venue->omakase_details }}
                    @else
                        Fees paid are for reservation only. Not applicable towards venue bill.
                    @endif
                </div>

                @if($this->record->status !== BookingStatus::CANCELLED)
                    <x-filament::button
                            wire:click="$dispatch('open-modal', { id: 'modify-booking-{{ $record->id }}' })"
                            class="w-full mt-3" icon="heroicon-m-pencil-square"
                            :disabled="$record->hasActiveModificationRequest()">
                        {{ $record->hasActiveModificationRequest() ? 'Modification Request Pending' : 'Modify Booking' }}
                    </x-filament::button>
                @endif

                <x-filament::actions :actions="[ $this->cancelBookingAction ]" class="w-full mt-4" />

                <x-filament::modal id="modify-booking-{{ $record->id }}" width="md">
                    <x-slot name="heading">
                        Modify Booking
                    </x-slot>

                    <x-slot name="description">
                        <div class="text-sm text-gray-500">
                            We must confirm all reservations with the participating venue. Please submit any
                            change
                            requests needed here. We will confirm the changes requested within 15-30 minutes and
                            notify
                            both you and the customer.
                        </div>
                    </x-slot>

                    <div>
                        <livewire:booking.modify-non-prime-booking-widget :booking="$record"
                                                                          :show-details="false" />
                    </div>
                </x-filament::modal>

                <x-filament-actions::modals />
            </div>

        </div>
        <!-- End Body -->
    </div>

</div>
