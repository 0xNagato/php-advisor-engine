@php
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
                    class="w-full px-4 py-2 mt-4 text-xs font-semibold text-white bg-indigo-600 rounded-lg sm:text-xs sm:text-sm">
                    Send Email
                </button>
            </form>
        @endif
    @endif

    <div
        class="bg-white rounded-xl shadow sm:max-w-3xl lg:mx-auto invoice-container flex flex-col @if ($download) min-h-[10in] @endif">
        <div class="relative overflow-hidden bg-indigo-800 min-h-32 rounded-t-xl">
            @if (!isset($customerInvoice) && !$download)
                <button class="p-4 font-semibold text-white" onclick="window.history.back();">
                    &#x276E;&nbsp Back
                </button>
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
                <svg class="flex-shrink-0 size-6" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                    fill="currentColor" viewBox="0 0 16 16">
                    <path
                        d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27zm.217 1.338L2 2.118v11.764l.137.274.51-.51a.5.5 0 0 1 .707 0l.646.647.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.509.509.137-.274V2.118l-.137-.274-.51.51a.5.5 0 0 1-.707 0L12 1.707l-.646.647a.5.5 0 0 1-.708 0L10 1.707l-.646.647a.5.5 0 0 1-.708 0L8 1.707l-.646.647a.5.5 0 0 1-.708 0L6 1.707l-.646.647a.5.5 0 0 1-.708 0L4 1.707l-.646.647a.5.5 0 0 1-.708 0l-.509-.51z" />
                    <path
                        d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5z" />
                </svg>
            </span>
            <!-- End Icon -->
        </div>

        <!-- Body -->
        <div class="p-4 sm:p-7">
            <div class="text-center">
                <h3 class="text-xl font-bold leading-5 tracking-tight text-gray-950">
                    PRIMA
                </h3>
                <p class="text-xs text-gray-500 sm:text-xs sm:text-sm">
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
                        {{ $booking->local_formatted_guest_phone }}
                        @if ($booking->guest_email)
                            <br />
                            {{ $booking->guest_email }}
                        @endif
                    </div>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Payment Method:</span>
                    <div class="flex items-center gap-x-2">
                        @if ($booking->stripe_charge)
                            @if ($booking->stripe_charge->paymentMethodDetails->card->brand === 'visa')
                                <x-fab-cc-visa class="w-6 h-6" />
                            @endif
                            @if ($booking->stripe_charge->paymentMethodDetails->card->brand === 'mastercard')
                                <x-fab-cc-mastercard class="w-6 h-6" />
                            @endif
                            @if ($booking->stripe_charge->paymentMethodDetails->card->brand === 'amex')
                                <x-fab-cc-amex class="w-6 h-6" />
                            @endif
                            <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                                ••••{{ $booking->stripe_charge->paymentMethodDetails->card->last4 }}</span>
                        @else
                            <span
                                class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">Unknown</span>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Booking Time:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $booking->booking_at->format('M d, Y g:i A') }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Date Paid:</span>
                    <span class="block text-xs font-medium text-gray-800 sm:text-sm dark:text-gray-200">
                        {{ $booking->confirmed_at->format('M d, Y g:i A') }}
                    </span>
                </div>

            </div>
            <!-- End Grid -->

            <div class="mt-5 sm:mt-10">
                @if ($booking->notes)
                    <h4 class="text-xs font-semibold text-gray-800 uppercase dark:text-gray-200">Notes</h4>
                    <p class="mb-3 text-xs text-gray-800 sm:text-xs sm:text-sm dark:text-gray-200">{{ $booking->notes }}
                    </p>
                @endif

                <h4 class="text-xs font-semibold text-gray-800 uppercase dark:text-gray-200">Summary</h4>

                <ul class="flex flex-col mt-3 overflow-hidden border rounded-lg">
                    <li
                        class="inline-flex items-center px-4 py-3 text-xs text-gray-800 border-b sm:text-sm gap-x-2 last:border-b-0 dark:border-gray-700 dark:text-gray-200">
                        <div class="flex flex-col w-full">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-medium">{{ $booking->venue->name }} ({{ $booking->guest_count }}
                                    guests)</span>
                                <span class="font-medium">
                                    {{ money($booking->total_fee, $booking->currency) }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ money($booking->venue->booking_fee * 100, $booking->currency) }} for 2,
                                {{ money($booking->venue->increment_fee * 100, $booking->currency) }}/additional guest
                            </div>
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
                    <li
                        class="inline-flex items-center px-4 py-3 text-xs font-semibold text-gray-800 sm:text-sm bg-gray-50 dark:bg-slate-800 dark:text-gray-200">
                        <div class="flex items-center justify-between w-full">
                            <span>Amount Paid</span>
                            <span>
                                {{ money($booking->total_with_tax_in_cents, $booking->currency) }}
                            </span>
                        </div>
                    </li>
                </ul>

                @if (isset($customerInvoice) || $download)
                    <div class="mt-4 font-semibold text-center">
                        Fees paid are for reservation only. Not applicable towards venue bill.
                    </div>
                @endif
                @if (auth()->check() && auth()->user()->hasRole('super_admin'))
                    <x-filament::actions :actions="$this->resendInvoiceAction" class="w-full" />
                @endif
            </div>

            @if (auth()->check() && auth()->user()->hasRole('super_admin'))
                @php
                    $booking->load('earnings.user.venue', 'earnings.user.concierge', 'earnings.user.partner');
                    // Eager loading because resend customer invoice would break everytime
                    // I'll buy you lunch if you fix this and can explain why this is happening
                    // - Andrew
                @endphp
                <div class="mt-6">
                    <div class="flex flex-col gap-8 lg:flex-row">
                        <div class="w-full lg:w-1/2">
                            <div class="mb-4 text-xs font-semibold capitalize">Earnings Breakdown</div>
                            <div class="grid grid-cols-3 gap-2 pb-4 mb-4 text-xs border-b">
                                @foreach ($booking->earnings as $earning)
                                    <div class="truncate" title="{{ $earning->user->name }}">
                                        @php
                                            $earning->user->load('venue', 'concierge', 'partner');
                                            $url = match ($earning->type) {
                                                'venue' => VenueResource::getUrl('view', [
                                                    'record' => $earning->user->venue->id,
                                                ]),
                                                'concierge_referral_1',
                                                'concierge_referral_2',
                                                'concierge'
                                                    => ConciergeResource::getUrl('view', [
                                                    'record' => $earning->user->concierge->id,
                                                ]),
                                                'partner',
                                                'partner_concierge',
                                                'partner_venue'
                                                    => PartnerResource::getUrl('view', [
                                                    'record' => $earning->user->partner->id,
                                                ]),
                                                default => null,
                                            };
                                        @endphp
                                        @if ($url)
                                            <a href="{{ $url }}" class="text-indigo-600 hover:underline">
                                                {{ $earning->user->name }}
                                            </a>
                                        @else
                                            {{ $earning->user->name }}
                                        @endif
                                    </div>
                                    <div>
                                        @php
                                            $type = match ($earning->type) {
                                                'concierge_referral_1' => 'Con. Ref. 1',
                                                'concierge_referral_2' => 'Con. Ref. 2',
                                                'partner_concierge' => 'Partner Con.',
                                                'partner_venue' => 'Partner Venue',
                                                default => \Illuminate\Support\Str::title($earning->type),
                                            };
                                        @endphp
                                        {{ $type }}
                                    </div>
                                    <div class="text-right">
                                        {{ money($earning->amount, $booking->currency) }}
                                    </div>
                                @endforeach

                                <div class="col-span-2">Platform Earnings:</div>
                                <div class="text-right">
                                    {{ money($booking->platform_earnings, $booking->currency) }}
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-xs font-semibold">
                                <div class="col-span-2">Total Amount:</div>
                                <div class="text-right">
                                    {{ money($booking->total_with_tax_in_cents, $booking->currency) }}
                                </div>
                            </div>

                        </div>
                        <div class="w-full -mt-2 lg:w-1/2">
                            <livewire:payout-breakdown-chart :booking="$booking" />
                        </div>
                    </div>
                </div>
            @endif

        </div>
        <!-- End Body -->
    </div>
    <x-filament-actions::modals />
</div>
