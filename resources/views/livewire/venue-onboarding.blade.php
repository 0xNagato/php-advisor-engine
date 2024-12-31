<x-layouts.simple-wrapper>
    <div class="max-w-xl mx-auto">
        @if ($submitted)
            <div class="text-center">
                <h3 class="text-lg font-medium text-gray-900">Submission Received</h3>
                <p class="mt-2 text-sm text-gray-500">We'll be in touch shortly to complete your onboarding.</p>
            </div>
        @else
            <div class="flex items-center justify-center gap-2 mb-6 sm:gap-4">
                @php
                    $steps = [
                        'company' => 'Company',
                        'venues' => 'Venues',
                        'prime-hours' => 'Hours',
                        'incentive' => 'Incentives',
                        'agreement' => 'Agreement',
                    ];
                @endphp

                @foreach ($steps as $key => $label)
                    <div class="flex flex-col items-center gap-1 sm:gap-2">
                        <div @class([
                            'w-6 h-6 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-medium',
                            'bg-indigo-600 text-white' => $step === $key,
                            'bg-gray-100 text-gray-600' => $step !== $key,
                        ])>
                            {{ $loop->iteration }}
                        </div>
                        <span class="text-[10px] sm:text-sm font-semibold text-gray-700">{{ $label }}</span>
                    </div>
                    @unless ($loop->last)
                        <div class="self-center -translate-y-3 h-[2px] w-4 sm:w-8 bg-gray-200"></div>
                    @endunless
                @endforeach
            </div>

            <form wire:submit.prevent="{{ $step === 'agreement' ? 'submit' : 'nextStep' }}" x-data
                x-on:submit="window.scrollTo({top: 0, behavior: 'smooth'})">
                <div class="space-y-6">
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

                            <x-type-ahead label="Name of PRIMA Partner" placeholder="Search for a partner..."
                                :items="$partners" wire-model="partner_id" :error="$errors->first('partner_id')" />

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
                                <label class="block mb-2 text-sm text-gray-700">How many venues are you adding to
                                    PRIMA?</label>
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
                                <label class="block text-sm text-gray-700">Please list the names of the venues</label>
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
                                                :error="$errors->first('logo_files.' . $index)" wire:click="deleteUpload([], {{ $index }})" />
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
                                <h3 class="text-lg font-medium text-gray-900">
                                    {{ $venue_names[$current_venue_index] ?: 'Venue ' . ($current_venue_index + 1) }}
                                    Prime Hours
                                </h3>
                                <p class="mt-2 text-sm text-gray-500">
                                    Please specify which hours of the day this restaurant is usually fully booked.
                                    During these times, customers will be given the option to purchase a reservation.
                                </p>
                                <div class="flex items-center gap-1 mt-4 text-sm text-gray-500">
                                    @foreach ($venue_names as $index => $name)
                                        <span @class([
                                            'w-6 h-6 flex items-center justify-center rounded-full text-xs font-medium',
                                            'bg-indigo-600 text-white' => $index === $current_venue_index,
                                            'bg-gray-100 text-gray-600' => $index !== $current_venue_index,
                                        ])>
                                            {{ $index + 1 }}
                                        </span>
                                        @unless ($loop->last)
                                            <div class="w-4 h-px bg-gray-200"></div>
                                        @endunless
                                    @endforeach
                                </div>
                            </div>

                            <div class="space-y-6">
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayIndex => $dayName)
                                    @php $day = strtolower($dayName); @endphp
                                    <div>
                                        <h4 class="mb-3 text-sm font-medium text-gray-700">{{ $dayName }}</h4>
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
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Incentive Step --}}
                    @if ($step === 'incentive')
                        <div>
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900">Non-Prime Reservation Incentive Program
                                </h3>
                                <p class="mt-2 text-sm text-gray-500">
                                    Configure the non-prime reservation incentive program for each venue.
                                </p>
                                <div class="flex items-center gap-1 mt-4 text-sm text-gray-500">
                                    @foreach ($venue_names as $index => $name)
                                        <span @class([
                                            'w-6 h-6 flex items-center justify-center rounded-full text-xs font-medium',
                                            'bg-indigo-600 text-white' => $index === $current_venue_index,
                                            'bg-gray-100 text-gray-600' => $index !== $current_venue_index,
                                        ])>
                                            {{ $index + 1 }}
                                        </span>
                                        @unless ($loop->last)
                                            <div class="w-4 h-px bg-gray-200"></div>
                                        @endunless
                                    @endforeach
                                </div>
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
                    <div class="pb-20"></div>
                </div>
            </form>
        @endif
    </div>
</x-layouts.simple-wrapper>
