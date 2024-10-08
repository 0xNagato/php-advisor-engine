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
                    We are excited to have you as a part of our team when PRIMA fully launches. Venues are being
                    onboarded now and we expect to be fully functioning in the coming weeks.
                </p>
                @nonmobileapp
                    <p>
                        So that you may fully experience the potential of PRIMA, we have created a demo concierge account
                        for you to explore.
                    </p>
                    <p>
                        Please click the link below and you will be taken to our demo site where you can
                        try out the Reservation Hub as well as other features of PRIMA.
                    </p>
                    <x-filament::button tag="a"
                        href="https://demo.primavip.co/login?email=concierge@primavip.co&password=demo2024" target="_blank">
                        Access Demo Account
                    </x-filament::button>
                @endnonmobileapp

                <div>
                    <p>Welcome aboard!</p>
                    <p>We look forward to working with you.</p>
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
