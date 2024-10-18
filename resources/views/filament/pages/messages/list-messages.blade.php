@php
    $isDemo = Request::getHost() === 'demo.primavip.co';
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            @if ($isDemo)
                Welcome to the PRIMA Demo!
            @else
                Welcome to PRIMA!
            @endif
        </x-slot>

        <x-slot name="headerEnd">
            @if (!$isDemo)
                <div class="text-xs text-gray-500">
                    {{ auth()->user()->created_at->format('M j h:ia') }}
                </div>
            @endif
        </x-slot>

        @nonmobileapp
        <div class="flex flex-col items-center p-3 mb-4 -m-2 text-sm border-2 border-indigo-600 rounded-lg bg-gray-50">
            <div class="flex items-center mb-3">
                <img src="https://is1-ssl.mzstatic.com/image/thumb/Purple221/v4/2f/36/1f/2f361f98-4d81-10ab-66d6-66d207e66c6a/AppIcon-0-0-1x_U007epad-0-85-220.png/230x0w.webp"
                     class="w-12 h-12 mr-4 rounded-lg" alt="Apple App Store Logo">
                <div>
                    For the best experience, please download the PRIMA App for iPhone!
                </div>
            </div>
            <a href="https://apps.apple.com/us/app/prima-vip/id6504947227?platform=iphone" target="_blank">
                <img src="https://developer.apple.com/app-store/marketing/guidelines/images/badge-example-preferred_2x.png"
                     alt="Download on the App Store" class="h-10">
            </a>
        </div>
        @endnonmobileapp

        <div class="flex flex-col gap-4">
            @if ($isDemo)
                <p>
                    Please browse this account to see what earnings, bookings, the availability calendar and other
                    features of the site look like!
                </p>
                <p>
                    You can generate just as much revenue in your account if you use PRIMA regularly.
                </p>
                <p>
                    We look forward to working with you!
                </p>
            @else
                <p>
                    Hi {{ auth()->user()->first_name }},
                </p>
                <p>
                    Welcome to PRIMA!
                </p>
                <p>
                    Thank you for joining our concierge team! Over the next several weeks, we are onboarding restaurants
                    and concierges who will be utilizing our system to book reservations at top venues in Miami.
                </p>
                <p class="px-4 py-2 text-sm font-semibold text-green-700 border-2 border-green-200 rounded-lg bg-green-50">
                    We plan to begin selling reservations by mid-November as the Miami season heats up.
                </p>
                <p>
                    @nonmobileapp
                    Please browse the
                    <a class="font-semibold text-indigo-600 underline"
                       href="{{ route('filament.admin.pages.availability-calendar') }}">
                        availability calendar
                    </a> and
                    <a class="font-semibold text-indigo-600 underline"
                       href="{{ route('filament.admin.pages.concierge.reservation-hub') }}">
                        reservation hub
                    </a> to see
                    available venues.
                    @endnonmobileapp
                    @mobileapp
                    Please browse the availability calendar and reservation hub to see available venues.
                    @endmobileapp
                    If there is a
                    restaurant that you'd like to see on the platform, please
                    <a href="#" x-data=""
                       x-on:click.prevent="$dispatch('open-modal', { id: 'contact-us-modal' })"
                       class="font-semibold text-indigo-600 underline hover:text-indigo-800">
                        contact us</a>.
                </p>
                <p>
                    Begin building your team and refer others to PRIMA today. Those that start first will earn most. We
                    are excited to have you on our team!
                </p>
                <div>
                    <p>Welcome aboard!</p>
                    <p>Team PRIMA</p>
                </div>
            @endif
        </div>
    </x-filament::section>

    @if ($messages->isNotEmpty())
        <div class="flex flex-col gap-0 -mt-4 bg-white divide-y rounded-lg shadow-lg">
            @foreach ($this->messages as $message)
                <div class="p-4">
                    <a href="{{ route('filament.admin.resources.messages.view', ['record' => $message->id]) }}">
                        <div class="flex flex-row items-center">
                            <div class="w-2/12 mr-2 sm:w-1/12">
                                <x-filament::avatar src="{{ $message->announcement->sender->getFilamentAvatarUrl() }}"
                                                    alt="User Avatar" size="w-12 h-12"/>
                            </div>

                            <div class="w-10/12">
                                <div class="flex flex-row items-center">
                                    <span class="mr-1 font-semibold">
                                        {{ $message->announcement->sender->name }}
                                    </span>
                                    @if (is_null($message->read_at))
                                        <x-heroicon-s-information-circle
                                                class="h-4 w-4 -mt-0.5 text-xs text-green-600"/>
                                    @endif
                                    <span class="self-start text-xs text-right grow">
                                        {{ $message->created_at->format('M j h:i A') }}
                                    </span>
                                </div>
                                <p class="text-xs truncate">
                                    {{ $message->announcement->message }}
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    <x-filament::modal id="contact-us-modal" width="md">
        <x-slot name="heading">
            Contact Us
        </x-slot>

        <form wire:submit="submitContactForm">
            {{ $this->form }}

            <div class="flex justify-end mt-4">
                <x-filament::button type="submit">
                    Send Message
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
</x-filament-panels::page>
