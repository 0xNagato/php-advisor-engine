@php
    $isDemo = Request::getHost() === 'demo.primavip.co';
@endphp

<x-filament-panels::page>
    <x-filament::section class="text-xs sm:text-base">
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
            <div
                class="flex flex-col items-center p-3 mb-4 -m-2 text-xs border-2 border-indigo-600 rounded-lg sm:text-base bg-gray-50">
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

        <div class="flex justify-center -mx-6 sm:hidden">
            <img src="{{ asset('images/concierge-earnings-infographic-new.png') }}" alt="Concierge Earnings Infographic"
                class="w-full">
        </div>

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
                    PRIMA is currently LIVE in Miami, and you may begin booking reservations starting immediately.
                </p>
                <p>
                    Nearly 35 of the top restaurants in Miami have expressed interest in joining PRIMA, and some are
                    ahead of others in the process of onboarding. We appreciate your patience.
                </p>
                <p>
                    @nonmobileapp
                        The entire list of restaurants can be seen in the
                        <a class="font-semibold text-indigo-600 underline"
                            href="{{ route('filament.admin.pages.availability-calendar') }}">
                            Availability Calendar
                        </a>.
                    @endnonmobileapp
                    @mobileapp
                        The entire list of restaurants can be seen in the Availability Calendar.
                    @endmobileapp
                </p>
                <p>
                    We expect all restaurants to be finished onboarding within the next few weeks as we continue to
                    bring on new restaurant partners through the US and Europe.
                </p>
                <p>
                    We welcome your feedback as PRIMA continues to grow and evolve, please do not hesitate to contact us
                    at
                    <a href="mailto:prima@primavip.co" class="font-semibold text-indigo-600 underline">
                        prima@primavip.co
                    </a>
                    if you have any questions.
                </p>
            @endif
        </div>

        {{-- <div class="mt-6 text-center">
            <p class="mb-2 text-xs font-medium sm:text-base">Watch this to learn how PRIMA works</p>
            <div class="overflow-hidden rounded-lg aspect-w-9 aspect-h-16">
                <iframe src="https://player.getclipara.com/share/8426?autoplay=0" class="w-full h-full"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
        </div> --}}
    </x-filament::section>

    @if ($messages->isNotEmpty())
        <div class="flex flex-col gap-0 -mt-4 bg-white divide-y rounded-lg shadow-lg">
            @foreach ($this->messages as $message)
                <div class="p-4">
                    <a href="{{ route('filament.admin.resources.messages.view', ['record' => $message->id]) }}">
                        <div class="flex flex-row items-center">
                            <div class="w-2/12 mr-2 sm:w-1/12">
                                <x-filament::avatar src="{{ $message->announcement->sender->getFilamentAvatarUrl() }}"
                                    alt="User Avatar" size="w-12 h-12" />
                            </div>

                            <div class="w-10/12">
                                <div class="flex flex-row items-center">
                                    <span class="mr-1 font-semibold">
                                        {{ $message->announcement->sender->name }}
                                    </span>
                                    @if (is_null($message->read_at))
                                        <x-heroicon-s-information-circle
                                            class="h-4 w-4 -mt-0.5 text-xs text-green-600" />
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
</x-filament-panels::page>
