@php
    use App\Enums\BookingStatus;
    use App\Enums\VenueStatus;
@endphp
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
<x-filament-panels::page>
    <div x-data="{}" x-init="() => {
        let script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        document.head.appendChild(script);
    }" class="flex flex-col gap-4">
        @if (!$booking)
            {{ $this->form }}

            <div class="flex flex-col gap-2">
                @if ($this->isDateBeyondLimit())
                    <div class="text-center py-12">
                        <div class="mx-auto max-w-md">
                            <div class="rounded-lg bg-gray-50 p-6">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900">Date Too Far in Advance</h3>
                                <p class="mt-2 text-sm text-gray-600">
                                    We only show availability for the next {{ $this->getMaxReservationDays() }} days. 
                                    Please select a date within this range.
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif ($schedulesToday->count() || $schedulesThisWeek->count())
                    @if ($schedulesToday->count())

                        <div id="time-slots" class="grid grid-cols-3 gap-2 md:grid-cols-5">
                            @foreach ($schedulesToday as $index => $schedule)
                                <button
                                    @if ($schedule->is_bookable && !$schedule->is_within_buffer && $schedule->venue->status === VenueStatus::ACTIVE) wire:click="createBooking({{ $schedule->schedule_template_id }})" @endif
                                    @class([
                                        'flex flex-col gap-1 items-center p-3 text-sm font-semibold leading-none rounded-xl justify-center',
                                        'outline outline-2 outline-offset-2 outline-green-600' =>
                                            $schedule->start_time === $data['reservation_time'] &&
                                            $schedule->is_bookable &&
                                            !$schedule->is_within_buffer &&
                                            $schedule->venue->status === VenueStatus::ACTIVE,
                                        'outline outline-2 outline-offset-2 outline-gray-100' =>
                                            $schedule->start_time === $data['reservation_time'] &&
                                            (!$schedule->is_bookable || $schedule->is_within_buffer),
                                        'bg-green-600 text-white cursor-pointer hover:bg-green-500' =>
                                            $schedule->is_bookable &&
                                            !$schedule->is_within_buffer &&
                                            $schedule->venue->status === VenueStatus::ACTIVE,
                                        'bg-gray-100 text-gray-400 border-none cursor-default' =>
                                            !$schedule->is_bookable ||
                                            $schedule->is_within_buffer ||
                                            $schedule->venue->status === VenueStatus::PENDING,
                                        'hidden md:block' =>
                                            $index === 0 || $index === $schedulesToday->count() - 1,
                                    ])>
                                    <div class="text-lg text-center">
                                        {{ $schedule->formatted_start_time }}
                                    </div>
                                    <div>
                                        @if ($schedule->venue->status === VenueStatus::PENDING)
                                            <span class="text-xs font-semibold">Soon</span>
                                        @elseif ($schedule->is_bookable && $schedule->prime_time)
                                            @money($schedule->fee($data['guest_count']), $currency)
                                        @elseif($schedule->is_bookable && !$schedule->prime_time)
                                            No Fee
                                        @endif
                                    </div>
                                    @if ($schedule->is_bookable && $schedule->remaining_tables <= 5)
                                        <div
                                            class="bg-red-500 [text-shadow:_0_1px_0_rgb(0_0_0_/_40%)] mt-1 px-2 py-1 border border-red-900 text-white text-[12px] text-nowrap rounded">
                                            {{ $schedule->remaining_tables === 1 ? 'Last Table' : 'Last Tables' }}
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                    @endif

                    @if ($schedulesThisWeek->count())
                        <div class="mt-2 text-sm font-bold text-center uppercase">
                            Next {{ self::AVAILABILITY_DAYS }} Days Availability
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ($schedulesThisWeek as $schedule)
                                <div
                                    @if ($schedule->is_bookable && $schedule->venue->status === VenueStatus::ACTIVE) wire:click="createBooking({{ $schedule->schedule_template_id }}, '{{ $schedule->booking_date->format('Y-m-d') }}')" @endif
                                    @class([
                                        'flex flex-col gap-1 items-center px-3 py-3 text-sm font-semibold leading-none rounded-xl',
                                        'bg-green-600 text-white cursor-pointer hover:bg-green-500' =>
                                            $schedule->is_bookable &&
                                            $schedule->venue->status === VenueStatus::ACTIVE,
                                        'bg-gray-100 text-gray-400 border-none cursor-default' =>
                                            !$schedule->is_bookable ||
                                            $schedule->venue->status === VenueStatus::PENDING,
                                    ])>
                                    <div class="text-xs text-center">
                                        {{ $schedule->booking_date->format('D, M jS') }}
                                    </div>
                                    <div class="text-lg text-center">
                                        {{ $schedule->formatted_start_time }}
                                    </div>
                                    <div>
                                        @if ($schedule->venue->status === VenueStatus::PENDING)
                                            <span class="text-xs font-semibold">Soon</span>
                                        @elseif ($schedule->is_bookable && $schedule->prime_time)
                                            @money($schedule->fee($data['guest_count']), $currency)
                                        @elseif($schedule->is_bookable && !$schedule->prime_time)
                                            No Fee
                                        @endif
                                    </div>
                                    @if ($schedule->is_bookable && $schedule->remaining_tables <= 5)
                                        <div
                                            class="bg-red-500 [text-shadow:_0_1px_0_rgb(0_0_0_/_40%)] mt-1 px-2 py-1 border border-red-900 text-white text-[12px] text-nowrap rounded">
                                            {{ $schedule->remaining_tables === 1 ? 'Last Table' : 'Last Tables' }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                    @endif

                @endif
            </div>

        @endif

        @if ($booking && (BookingStatus::PENDING === $booking->status || BookingStatus::GUEST_ON_PAGE === $booking->status))
            <livewire:booking.check-if-confirmed :booking="$booking" />

            <div x-data="{ tab: '{{ $booking->prime_time ? 'smsPayment' : 'collectPayment' }}' }" id="tabs">
                @if ($booking->prime_time)
                    <div class="flex space-x-4">
                        <div class="flex space-x-4 w-full text-xs">
                            <button
                                :class="{ 'bg-indigo-600 text-white': tab === 'smsPayment', 'bg-gray-100': tab !== 'smsPayment' }"
                                @click="tab = 'smsPayment'"
                                class="flex gap-1 items-center px-4 py-2 text-xs font-semibold bg-gray-100 rounded-lg shadow-lg shadow-gray-400">
                                <x-gmdi-phone-android-r class="w-6 h-6 font-semibold" />
                                <div>SMS</div>
                            </button>
                            <button
                                :class="{ 'bg-indigo-600 text-white': tab === 'qrCode', 'bg-gray-100': tab !== 'qrCode' }"
                                @click="tab = 'qrCode'"
                                class="flex gap-1 items-center px-4 py-2 text-xs font-semibold bg-gray-100 rounded-lg shadow-lg shadow-gray-400">
                                <x-gmdi-qr-code class="w-6 h-6 font-semibold" />
                                <div>QR Code</div>
                            </button>
                            @nonmobileapp
                            <button
                                :class="{ 'bg-indigo-600 text-white': tab === 'collectPayment', 'bg-gray-100': tab !== 'collectPayment' }"
                                @click="tab = 'collectPayment'"
                                class="flex gap-1 items-center px-4 py-2 text-xs font-semibold bg-gray-100 rounded-lg shadow-lg shadow-gray-400">
                                <x-gmdi-credit-card class="w-6 h-6 font-semibold text-center" />
                                <div>Collect CC</div>
                            </button>
                            @endnonmobileapp
                        </div>

                    </div>
                    <div class="mt-2 w-full">
                        @if ($booking->schedule->minimum_spend_per_guest > 0)
                            <div
                                class="p-3 mb-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg border border-blue-200">
                                <strong>Important:</strong> This reservation requires a
                                ${{ $booking->schedule->minimum_spend_per_guest }} per diner minimum spend. Booking
                                fees do not apply toward minimum spend or restaurant bill.
                            </div>
                        @endif
                        <livewire:booking.invoice-small :booking="$booking" />
                        @if ($booking->venue->is_omakase)
                            <div
                                class="p-3 mt-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg border border-indigo-200">
                                {{ $booking->venue->omakase_details }}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="-mt-6"></div>
                @endif

                <div x-show="tab === 'collectPayment'" class="mt-4">
                    <!-- @todo Refactor this to a separate component -->

                    <div wire:ignore class="flex flex-col gap-3 items-center" x-data="{}"
                         x-init="() => {
                            function initializeStripe() {
                                if (window.Stripe) {
                                    setupStripe();
                                } else {
                                    setTimeout(initializeStripe, 10);
                                }
                            }

                            function setupStripe() {
                                const stripe = Stripe('{{ config('services.stripe.key') }}');
                                const elements = stripe.elements();
                                const card = elements.create('card', {
                                    disableLink: true,
                                    hidePostalCode: true
                                });
                                card.mount('#card-element');

                                const form = document.getElementById('form');

                                form.addEventListener('submit', async (e) => {
                                    e.preventDefault();

                                    // Phone validation
                                    const phoneInput = document.getElementById('phone');
                                    const phoneError = document.getElementById('phone-error');

                                    // Reset the error
                                    phoneError.classList.add('hidden');
                                    phoneError.textContent = '';

                                    // Check if the international telephone input has been initialized
                                    if (phoneInput.getAttribute('data-intl-tel-input-id')) {
                                        const iti = window.intlTelInputGlobals.getInstance(phoneInput);

                                        if (!iti.isValidNumber()) {
                                            phoneError.textContent = 'Please enter a valid phone number';
                                            phoneError.classList.remove('hidden');
                                            $wire.$set('isLoading', false);
                                            return;
                                        }

                                        // Set the phone number in E.164 format for submission
                                        phoneInput.value = iti.getNumber();
                                    }

                                    $wire.$set('isLoading', true);

                                    @if($booking->prime_time)
                                    const { token, error } = await stripe.createToken(card);

                                    if (error) {
                                        $wire.$set('isLoading', false);
                                        return;
                                    }
                                    @else
                                    var token = { id: '' };
                                    @endif

                                    const formData = {
                                        first_name: document.querySelector('input[name=first_name]').value,
                                        last_name: document.querySelector('input[name=last_name]').value,
                                        phone: document.querySelector('input[name=phone]').value,
                                        email: document.querySelector('input[name=email]').value,
                                        notes: document.querySelector('textarea[name=notes]').value,
                                        token: token?.id ?? ''
                                    };

                                    @if(!$booking->prime_time)
                                    formData.real_customer_confirmation = document.querySelector('input[name=real_customer_confirmation]').checked;
                                    @endif

                                    $wire.$call('completeBooking', formData);
                                })

                            }

                            initializeStripe();
                        }">

                        @if ($booking && !$booking->is_prime)
                            <div class="w-full">
                                @if ($booking->schedule->minimum_spend_per_guest > 0)
                                    <div
                                        class="p-3 mb-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg border border-blue-200">
                                        <strong>Important:</strong> This booking time requires a
                                        ${{ $booking->schedule->minimum_spend_per_guest }} per diner minimum spend.
                                        Booking fees do not apply toward minimum spend or restaurant bill.
                                    </div>
                                @endif
                                <livewire:booking.invoice-small :booking="$booking" />
                            </div>
                        @endif

                        <form id="form" class="w-full">
                            <fieldset class="flex flex-col gap-2 items-center disabled:opacity-50">
                                <div class="flex gap-2 items-center w-full">
                                    <label class="w-full">
                                        <input name="first_name" type="text"
                                               class="w-full rounded-lg border border-gray-300 text-sm h-[40px] focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                               placeholder="First Name" required>
                                    </label>

                                    <label class="w-full">
                                        <input name="last_name" type="text"
                                               class="w-full rounded-lg border border-gray-300 text-sm h-[40px] focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                               placeholder="Last Name" required>
                                    </label>

                                </div>

                                <div class="w-full phone-input-container">
                                    <label class="w-full">
                                        <input name="phone" type="tel" id="phone"
                                               class="w-full rounded-lg border border-gray-300 text-sm h-[40px] focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                               placeholder="Cell Phone Number" required>
                                    </label>
                                    <div id="phone-error" class="hidden mt-1 text-sm text-red-500"></div>
                                    <div class="mt-1 w-full text-xs text-gray-500">
                                        Guest Phone Number is required. Please do not use concierge phone number here.
                                    </div>
                                </div>

                                <label class="w-full">
                                    <input name="email" type="email"
                                           class="w-full rounded-lg border border-gray-300 text-sm h-[40px] focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                           placeholder="Email Address" required>
                                </label>
                                <div class="w-full text-xs text-gray-500">
                                    Email address is mandatory. If you cannot share the guest's email address, please
                                    enter prima@primavip.co
                                </div>

                                <label class="w-full">
                                    <textarea name="notes"
                                              class="w-full text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                              placeholder="Notes/Special Requests (optional)"></textarea>
                                </label>

                                <div id="card-element"
                                     class="-mt-1.5 w-full rounded-lg border border-gray-400 text-sm bg-white px-2 py-3 h-[40px] {{ !$booking->prime_time ? 'hidden' : '' }}">
                                    <!-- A Stripe Element will be inserted here. -->
                                </div>

                                @if (!$booking->prime_time)
                                    <label class="flex items-center mb-2 w-full text-xs">
                                        <input type="checkbox" name="real_customer_confirmation"
                                               class="text-indigo-600 rounded form-checkbox">
                                        <span class="ml-2 text-xs text-gray-700">I am booking this reservation for a
                                            real customer</span>
                                    </label>
                                @endif

                                @if ($booking->prime_time)
                                    <label class="flex items-center mt-4 mb-2 w-full text-xs">
                                        <input type="checkbox" name="prime_time_fee_confirmation" required
                                               class="text-indigo-600 rounded form-checkbox">
                                        <span class="ml-2 text-xs text-gray-700 font-bold">I have informed the guest that this fee is for a prime time reservation and is not applied towards their bill at the restaurant.</span>
                                    </label>
                                @endif

                                <x-filament::button class="mt-2 w-full" type="submit" size="xl">
                                    Complete Reservation
                                </x-filament::button>
                            </fieldset>
                        </form>
                    </div>
                </div>

                <div x-show="tab === 'smsPayment'" class="flex flex-col gap-4 mt-2">
                    <!-- SMS Payment Link Tab Content -->
                    @env('local')
                        <x-filament::button tag="a" href="{{ $bookingUrl }}" target="_blank" color="success"
                                            icon="gmdi-open-in-new" class="w-full">
                            Open Booking URL
                        </x-filament::button>
                    @endenv
                    <livewire:booking.s-m-s-booking-form :booking="$booking" :booking-url="$bookingUrl" />
                </div>

                <div x-show="tab === 'qrCode'" class="flex flex-col gap-4 mt-4">
                    <!-- QR Code Tab Content -->
                    <div class="mb-2 text-base font-semibold">
                        Show QR code below to customer to accept secure payment.
                    </div>

                    <img src="{{ $qrCode }}" alt="QR Code" class="w-1/2 mx-auto shadow-lg max-w-[400px]">

                    <livewire:booking.booking-status-widget :booking="$booking" />
                </div>
            </div>

            <x-filament::button x-on:click="$dispatch('open-modal', { id: 'confirm-cancel-booking' })"
                                class="w-full opacity-50" color="gray">
                Abandon Reservation
            </x-filament::button>

            <x-filament::modal id="confirm-cancel-booking">
                <x-slot name="heading">
                    Confirm Cancellation
                </x-slot>

                <div class="text-gray-600">
                    Are you sure you want to abandon this reservation? This action cannot be undone.
                </div>

                <x-slot name="footerActions">
                    <x-filament::button x-on:click="close" color="gray">
                        Cancel
                    </x-filament::button>

                    <x-filament::button wire:click="cancelBooking" color="danger" x-on:click="close">
                        Yes, Abandon Reservation
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>
        @elseif($booking && $booking->status === BookingStatus::CONFIRMED)
            <div class="flex flex-col gap-3 items-center" id="form">
                <div class="text-xl font-semibold text-black divider divider-neutral">Reservation Received</div>
                <p class="text-center">Thank you for the booking!<br>We are notifying the venue now.</p>
            </div>

            @if ($scheduleTemplateId && $date)
                <x-filament::button wire:click="resetBookingAndReturnToAvailabilityCalendar"
                                    class="w-full bg-[#421fff] h-[48px]" icon="gmdi-calendar-month">
                    Back to Availability Calendar
                </x-filament::button>
            @else
                <x-filament::button wire:click="resetBooking" class="w-full bg-[#421fff] h-[48px]"
                                    icon="gmdi-restaurant-menu">
                    Back to Reservation Hub
                </x-filament::button>
            @endif

            <div class="flex gap-4">

                <x-filament::button tag="a" class="w-1/2" color="gray"
                                    :href="route('filament.admin.resources.bookings.view', ['record' => $booking])">
                    View Booking
                </x-filament::button>

                <x-filament::button tag="a" class="w-1/2" color="gray"
                                    :href="route('customer.invoice', ['token' => $booking->uuid])">
                    View Invoice
                </x-filament::button>

            </div>
        @endif
    </div>

    @include('partials.bookings-disabled-modal')
</x-filament-panels::page>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.3.3/js/intlTelInput.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.3.3/css/intlTelInput.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the phone input
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                const iti = window.intlTelInput(phoneInput, {
                    utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.3.3/js/utils.js',
                    initialCountry: 'us',
                    preferredCountries: ['us', 'ca'],
                    separateDialCode: true,
                    nationalMode: false,
                    formatOnDisplay: true,
                    autoPlaceholder: 'polite',
                    placeholderNumberType: 'MOBILE',
                    customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                        return 'Cell Phone Number';
                    },
                });

                // Apply custom styling to match the design
                const phoneContainer = phoneInput.closest('.phone-input-container');
                if (phoneContainer) {
                    // Remove any inline styles we added before since we have CSS now
                    const flagContainer = phoneContainer.querySelector('.iti');
                    const selectedFlag = phoneContainer.querySelector('.iti__selected-flag');

                    // Clean up any inline styles
                    if (flagContainer) {
                        flagContainer.style.width = '';
                        flagContainer.style.display = '';
                    }

                    if (selectedFlag) {
                        selectedFlag.style.borderTopLeftRadius = '';
                        selectedFlag.style.borderBottomLeftRadius = '';
                        selectedFlag.style.backgroundColor = '';
                        selectedFlag.style.paddingLeft = '';
                    }

                    // Clean up input inline styles
                    phoneInput.style.paddingLeft = '';
                }

                // Validate the phone number on form submit
                const form = phoneInput.closest('form');
                const phoneError = document.getElementById('phone-error');

                if (form) {
                    form.addEventListener('submit', function(e) {
                        // Reset error messages
                        phoneError.classList.add('hidden');
                        phoneError.textContent = '';

                        if (!iti.isValidNumber()) {
                            e.preventDefault();
                            phoneError.textContent = 'Please enter a valid phone number';
                            phoneError.classList.remove('hidden');
                            return false;
                        }

                        // Set the phone number in E.164 format
                        phoneInput.value = iti.getNumber();
                        return true;
                    });
                }
            }
        });
    </script>

    <style>
        /* Custom styling for the intl-tel-input to match other inputs */
        .iti {
            width: 100%;
            display: block;
        }

        .iti__flag-container {
            z-index: 10;
        }

        .iti__selected-flag {
            background-color: transparent !important;
        }

        .iti--separate-dial-code .iti__selected-dial-code {
            color: #4b5563;
            font-size: 0.875rem;
        }

        .iti--separate-dial-code .iti__selected-flag {
            background-color: #f3f4f6;
            border-right: 1px solid #e5e7eb;
            width: 68px;
        }

        .iti--separate-dial-code input {
            padding-left: 80px !important;
        }

        .iti input {
            border-radius: 0.5rem !important;
            height: 2.5rem !important;
        }

        .iti__country-list {
            border-radius: 0.5rem;
            border-color: #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-height: 200px;
        }

        .iti__country {
            padding: 8px 10px;
            font-size: 0.875rem;
        }

        /* Reset the background and hover styles */
        .iti__selected-flag:hover,
        .iti__selected-flag:focus {
            background-color: #f3f4f6 !important;
        }

        /* Style for the dropdown arrow */
        .iti__arrow {
            border-top: 4px solid #6b7280;
            margin-left: 3px;
        }
    </style>
@endpush
