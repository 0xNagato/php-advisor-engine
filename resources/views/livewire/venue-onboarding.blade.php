<x-layouts.simple-wrapper>
    <div class="max-w-xl mx-auto">
        @if ($submitted)
            <div class="text-center">
                @if ($isExistingVenueManager)
                    <h3 class="text-lg font-medium text-gray-900">New Venue Submitted</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Thank you for submitting your new venue. Our team will review the information
                        and add it to your venue group shortly.
                    </p>
                    <div class="mt-6">
                        <a href="@php
try {
                                echo route('filament.admin.pages.venue-manager-dashboard');
                            } catch (\Exception $e) {
                                echo url('/venue-manager');
                            } @endphp"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Return to Dashboard
                        </a>
                    </div>
                @else
                    <h3 class="text-lg font-medium text-gray-900">Submission Received</h3>
                    <p class="mt-2 text-sm text-gray-500">We'll be in touch shortly to complete your onboarding.</p>
                @endif
            </div>
        @else
            @if ($isExistingVenueManager)
                <div class="mb-6 text-center">
                    <h2 class="text-xl font-bold text-gray-900">Add a New Venue to Your Group</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Complete the form below to submit a new venue for addition to your venue group.
                        Our team will review and process your submission shortly.
                    </p>
                </div>
            @endif

            <div class="flex items-center justify-between mb-4 sm:mb-6 sm:justify-center sm:gap-4">
                @foreach ($steps as $key => $label)
                    @if (!$isExistingVenueManager || $key !== 'company')
                        <div class="flex flex-col items-center gap-0.5 sm:gap-2">
                            <div @class([
                                'w-6 h-6 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-medium',
                                'bg-indigo-600 text-white' => $step === $key,
                                'bg-gray-100 text-gray-600' => $step !== $key,
                            ])>
                                {{ !$isExistingVenueManager ? $loop->iteration : ($key === 'venues' ? 1 : ($key === 'booking-hours' ? 2 : ($key === 'prime-hours' ? 3 : ($key === 'incentive' ? 4 : 5)))) }}
                            </div>
                            <span @class([
                                'text-[10px] sm:text-sm font-medium whitespace-nowrap',
                                'text-indigo-600' => $step === $key,
                                'text-gray-500' => $step !== $key,
                            ])>
                                {{ $label }}
                            </span>
                        </div>
                        @unless ($loop->last || ($isExistingVenueManager && $loop->iteration === 1))
                            <div class="self-center -translate-y-3 h-[1px] sm:h-[2px] w-2 sm:w-8 bg-gray-200"></div>
                        @endunless
                    @endif
                @endforeach
            </div>

            <form wire:submit.prevent="{{ $step === 'agreement' ? 'submit' : 'nextStep' }}" x-data
                x-on:submit="window.scrollTo({top: 0, behavior: 'smooth'})">
                <div class="space-y-6">
                    {{-- Existing Account Notice --}}
                    @if ($existingAccountDetected)
                        <div class="p-4 mb-4 rounded-lg bg-blue-50 border border-blue-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Existing Account Detected</h3>
                                    <div class="mt-1 text-sm text-blue-700">
                                        <p>It looks like your {{ $existingAccountType }} is already registered in our
                                            system. Instead of creating a new account, you can:</p>
                                        <ul class="mt-2 ml-5 list-disc">
                                            <li>Login to your existing account</li>
                                            <li>Add new venues to your current account</li>
                                        </ul>
                                        <div class="mt-3">
                                            <a href="{{ route('login') }}"
                                                class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                                                Go to login page
                                                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Company Step --}}
                    @if ($step === 'company')
                        <div class="space-y-6">
                            <div>
                                <label class="block mb-2 text-sm text-gray-700">Company or Restaurant Group Name</label>
                                <input type="text" wire:model="company_name"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter company name">
                                @error('company_name')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            @if ($partnerId && $partnerName)
                                <div>
                                    <label class="block mb-2 text-sm text-gray-700">PRIMA Partner</label>
                                    <div class="flex items-center p-3 border border-gray-300 rounded-lg bg-gray-50">
                                        <span class="text-sm font-medium text-gray-800">{{ $partnerName }}</span>
                                        <span class="ml-2 text-xs text-gray-500">(Referring Partner)</span>
                                    </div>
                                    <input type="hidden" wire:model="partner_id">
                                </div>
                            @else
                                <x-type-ahead label="Name of PRIMA Partner" placeholder="Search for a partner..."
                                    :items="$partners" wire-model="partner_id" :error="$errors->first('partner_id')" />
                            @endif

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-2 text-sm text-gray-700">First Name</label>
                                    <input type="text" wire:model="first_name"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter first name">
                                    @error('first_name')
                                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm text-gray-700">Last Name</label>
                                    <input type="text" wire:model="last_name"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter last name">
                                    @error('last_name')
                                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block mb-2 text-sm text-gray-700">Email Address</label>
                                <input type="email" wire:model="email"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter email address">
                                @error('email')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block mb-2 text-sm text-gray-700">Phone Number</label>
                                <input type="tel" wire:model="phone"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter phone number">
                                @error('phone')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Venues Step --}}
                    @if ($step === 'venues')
                        <div class="space-y-6">
                            <div>
                                <label class="block mb-2 text-sm text-gray-700">
                                    @if ($isExistingVenueManager)
                                        How many venues would you like to add to your existing group?
                                    @else
                                        How many venues are you adding to PRIMA?
                                    @endif
                                </label>
                                <select wire:model.live="venue_count"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach (range(1, 5) as $count)
                                        <option value="{{ $count }}">
                                            {{ $count }}
                                            {{ Str::plural('Venue', $count) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('venue_count')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-4">
                                <label class="block text-sm text-gray-700">
                                    @if ($isExistingVenueManager)
                                        Please list the names of the new venues
                                    @else
                                        Please list the names of the venues
                                    @endif
                                </label>
                                @foreach ($venue_names as $index => $name)
                                    <div class="grid grid-cols-5 gap-4">
                                        <div class="col-span-3">
                                            <label class="block mb-2 text-sm text-gray-700">Venue Name</label>
                                            <input type="text" wire:model="venue_names.{{ $index }}"
                                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @error("venue_names.{$index}")
                                                <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block mb-2 text-sm text-gray-700">Region</label>
                                            <select wire:model="venue_regions.{{ $loop->index }}"
                                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                @foreach ($availableRegions as $region)
                                                    <option value="{{ $region['value'] }}">
                                                        {{ $region['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div>
                                <label class="flex items-start space-x-4">
                                    <input type="checkbox" wire:model.live="has_logos"
                                        class="w-6 h-6 mt-0.5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm">
                                        <span class="block font-medium text-gray-700">Upload Venue Logos</span>
                                        <span class="text-gray-500">Provide logos for each venue or let PRIMA source
                                            them</span>
                                    </span>
                                </label>

                                @if ($has_logos)
                                    <div class="mt-6 space-y-6">
                                        @foreach ($venue_names as $index => $name)
                                            <x-file-upload name="logo_{{ $index }}" :label="$name ?: 'Venue ' . ($index + 1) . ' Logo'"
                                                model="logo_files.{{ $index }}" :file="$logo_files[$index] ?? null"
                                                :error="$errors->first('logo_files.' . $index)"
                                                wire:click="deleteUpload([], {{ $index }})" />
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Agreement Step --}}
                    @if ($step === 'agreement')
                        <div class="space-y-4">
                            <div class="p-4 overflow-y-auto border border-gray-200 rounded-lg bg-gray-50 max-h-[60vh]">
                                @include('partials.venue-agreement', [
                                    'company_name' => $company_name,
                                    'venue_names' => $venue_names,
                                    'first_name' => $first_name,
                                    'last_name' => $last_name,
                                    'venue_use_non_prime_incentive' => $venue_use_non_prime_incentive,
                                    'venue_non_prime_per_diem' => $venue_non_prime_per_diem,
                                ])
                            </div>

                            <div class="space-y-3">
                                <label class="flex items-center space-x-4">
                                    <input type="checkbox" wire:model="agreement_accepted"
                                        class="w-6 h-6 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-gray-700">I accept the terms of the
                                        agreement</span>
                                </label>
                                @error('agreement_accepted')
                                    <span class="block mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror

                                <label class="flex items-center space-x-4">
                                    <input type="checkbox" wire:model="send_agreement_copy"
                                        class="w-6 h-6 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-gray-700">Send me a copy of this agreement
                                        via email</span>
                                </label>
                            </div>
                        </div>
                    @endif

                    {{-- Prime Hours Step --}}
                    @if ($step === 'prime-hours')
                        <div>
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="mr-2 text-base font-medium text-gray-900 sm:text-lg">
                                        <div class="text-sm font-semibold text-gray-500 sm:text-base">
                                            {{ $venue_names[$current_venue_index] ?: 'Venue ' . ($current_venue_index + 1) }}
                                        </div>
                                        Prime Hours
                                    </h3>
                                    <span class="flex-shrink-0 text-sm text-gray-500 whitespace-nowrap">
                                        Venue {{ $current_venue_index + 1 }} of {{ count($venue_names) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">
                                    Please specify which hours of the day this restaurant is usually fully booked.
                                    During these times, customers will be given the option to purchase a reservation.
                                </p>
                            </div>

                            <div class="space-y-6">
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayIndex => $dayName)
                                    @php
                                        $day = strtolower($dayName);
                                        $timeSlots = $availableTimeSlots[$day] ?? [];
                                    @endphp
                                    <div>
                                        <h4 class="mb-3 text-sm font-medium text-gray-700">{{ $dayName }}</h4>
                                        @if ($this->venue_booking_hours[$current_venue_index][$day]['closed'])
                                            <p class="text-sm text-gray-500">Venue is closed on {{ $dayName }}s
                                            </p>
                                        @elseif (empty($timeSlots))
                                            <p class="text-sm text-gray-500">No time slots available</p>
                                        @else
                                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                                                @foreach ($timeSlots as $time)
                                                    <label class="relative flex items-center justify-center">
                                                        <input type="checkbox"
                                                            wire:model="venue_prime_hours.{{ $current_venue_index }}.{{ $day }}.{{ $time }}"
                                                            class="sr-only peer" />
                                                        <div
                                                            class="w-full py-2 text-sm text-center bg-white border border-gray-200 rounded-lg cursor-pointer peer-checked:bg-indigo-50 peer-checked:border-indigo-600 peer-checked:text-indigo-600">
                                                            {{ Carbon\Carbon::createFromFormat('H:i:s', $time)->format('g:i A') }}
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Incentive Step --}}
                    @if ($step === 'incentive')
                        <div>
                            <div class="mb-6">
                                <div class="flex items-center justify-between w-full mb-2">
                                    <h3 class="mr-2 text-base font-medium text-gray-900 sm:text-lg">
                                        <div class="text-sm font-semibold text-gray-500 sm:text-base">
                                            {{ $venue_names[$current_venue_index] ?: 'Venue ' . ($current_venue_index + 1) }}
                                        </div>
                                        Non-Prime Reservation Incentive Program
                                    </h3>
                                    <span class="flex-shrink-0 text-sm text-gray-500 whitespace-nowrap">
                                        Venue {{ $current_venue_index + 1 }} of {{ count($venue_names) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">
                                    Configure the non-prime reservation incentive program for each venue.
                                </p>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-base font-medium text-gray-900">
                                        {{ $venue_names[$current_venue_index] ?: 'Venue ' . ($current_venue_index + 1) }}
                                    </h4>
                                    <label class="flex items-start mt-4 space-x-4">
                                        <input type="checkbox"
                                            wire:model.live="venue_use_non_prime_incentive.{{ $current_venue_index }}"
                                            class="w-6 h-6 mt-0.5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        <span class="text-sm">
                                            <span class="block font-medium text-gray-700">Enable Non-Prime
                                                Incentives</span>
                                            <span class="text-gray-500">Offer per-diner incentives to encourage
                                                concierges to book non-prime reservations</span>
                                        </span>
                                    </label>

                                    @if ($venue_use_non_prime_incentive[$current_venue_index] ?? false)
                                        <div class="mt-4">
                                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                                Amount to pay per diner (usually 10% of average per-diner check size)
                                            </label>
                                            <div class="relative rounded-lg shadow-sm">
                                                <div
                                                    class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">$</span>
                                                </div>
                                                <input type="number"
                                                    wire:model="venue_non_prime_per_diem.{{ $current_venue_index }}"
                                                    step="0.01" min="0"
                                                    class="block w-full border-gray-300 rounded-lg pl-7 focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Booking Hours Step --}}
                    @if ($step === 'booking-hours')
                        <div>
                            <div class="flex items-center justify-between w-full mb-2">
                                <h3 class="mr-1 text-base font-medium text-gray-900 sm:text-lg">
                                    <div class="text-sm font-semibold text-gray-500 sm:text-base">
                                        {{ $venue_names[$current_venue_index] ?: 'Venue ' . ($current_venue_index + 1) }}
                                    </div>
                                    Booking Hours
                                </h3>
                                <span class="flex-shrink-0 text-sm text-gray-500 whitespace-nowrap">
                                    Venue {{ $current_venue_index + 1 }} of {{ count($venue_names) }}
                                </span>
                            </div>
                            <p class="mb-4 text-sm text-gray-500">
                                Please specify the hours during which this venue would like to accept reservations
                                through PRIMA. These are the times when customers will be able to make bookings via our
                                platform.
                            </p>
                            <div class="space-y-6">
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayName)
                                    @php $day = strtolower($dayName); @endphp
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-gray-700">{{ $dayName }}</h4>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                    wire:model="venue_booking_hours.{{ $current_venue_index }}.{{ $day }}.closed"
                                                    class="sr-only peer">
                                                <div
                                                    class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600">
                                                </div>
                                                <span class="ml-2 text-sm font-medium text-gray-500">Closed</span>
                                            </label>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4" x-data
                                            x-show="!$wire.venue_booking_hours[{{ $current_venue_index }}]['{{ $day }}'].closed">
                                            <div>
                                                <label for="start-{{ $day }}"
                                                    class="block text-sm font-medium text-gray-700 sr-only">Opening
                                                    Time</label>
                                                <input type="time" id="start-{{ $day }}"
                                                    wire:model="venue_booking_hours.{{ $current_venue_index }}.{{ $day }}.start"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    step="1800">
                                                @error("venue_booking_hours.{$current_venue_index}.{$day}.start")
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="end-{{ $day }}"
                                                    class="block text-sm font-medium text-gray-700 sr-only">Closing
                                                    Time</label>
                                                <input type="time" id="end-{{ $day }}"
                                                    wire:model="venue_booking_hours.{{ $current_venue_index }}.{{ $day }}.end"
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    step="1800">
                                                @error("venue_booking_hours.{$current_venue_index}.{$day}.end")
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Navigation --}}
                    <div class="fixed inset-x-0 bottom-0 z-10 px-4 py-4 bg-white border-t sm:px-6">
                        <div class="flex justify-between max-w-xl mx-auto">
                            <div class="flex gap-2">
                                @if ($step !== 'company')
                                    <button type="button" wire:click="previousStep"
                                        class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Back
                                    </button>

                                    @if (config('app.env') === 'local')
                                        <button type="button" wire:click="resetForm"
                                            class="px-6 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50">
                                            Reset
                                        </button>
                                    @endif
                                @endif
                            </div>

                            <button type="submit"
                                class="{{ $step === 'company' ? 'w-full' : 'ml-auto' }} bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-6 py-2 text-sm font-medium">
                                {{ $step === 'agreement' ? 'Submit' : 'Continue' }}
                            </button>
                        </div>
                    </div>

                    {{-- Add padding at the bottom to prevent content from being hidden behind fixed buttons --}}
                    <div class="pb-10"></div>
                </div>
            </form>
        @endif
    </div>
</x-layouts.simple-wrapper>
