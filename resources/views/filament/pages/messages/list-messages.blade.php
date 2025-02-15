@php
    $isDemo = Request::getHost() === 'demo.primavip.co';
@endphp

<x-filament-panels::page>
    {{-- <livewire:comic-strip :pages="['/images/comic/1.webp', '/images/comic/2.webp', '/images/comic/3.webp', '/images/comic/4.webp']" /> --}}
    @nonmobileapp
        @php
            $userAgent = Request::userAgent();
            $isIosDevice =
                (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) &&
                !str_contains($userAgent, 'CriOS') &&
                !str_contains($userAgent, 'FxiOS');
            $isAndroidDevice = str_contains($userAgent, 'Android');
        @endphp
        @if ($isIosDevice)
            <div
                class="flex flex-col items-center p-3 -my-4 text-xs border-2 border-indigo-600 rounded-lg sm:text-base bg-gray-50">
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
        @elseif($isAndroidDevice)
            <div
                class="flex flex-col items-center p-3 -my-4 text-xs border-2 border-green-600 rounded-lg sm:text-base bg-gray-50">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-12 h-12 mr-4 text-green-600"
                        viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M6 18c0 .55.45 1 1 1h1v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h2v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h1c.55 0 1-.45 1-1V8H6v10zM3.5 8C2.67 8 2 8.67 2 9.5v7c0 .83.67 1.5 1.5 1.5S5 17.33 5 16.5v-7C5 8.67 4.33 8 3.5 8zm17 0c-.83 0-1.5.67-1.5 1.5v7c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-7c0-.83-.67-1.5-1.5-1.5zm-4.97-5.84l1.3-1.3c.2-.2.2-.51 0-.71-.2-.2-.51-.2-.71 0l-1.48 1.48C13.85 1.23 12.95 1 12 1c-.96 0-1.86.23-2.66.63L7.85.15c-.2-.2-.51-.2-.71 0-.2.2-.2.51 0 .71l1.31 1.31C6.97 3.26 6 5.01 6 7h12c0-1.99-.97-3.75-2.47-4.84zM10 5H9V4h1v1zm5 0h-1V4h1v1z" />
                    </svg>
                    <div>
                        The PRIMA App for Android devices is currently in final stages of testing. Please use the web
                        version of PRIMA for now. Thank you for your patience.
                    </div>
                </div>
            </div>
        @endif
    @endnonmobileapp
    <x-filament::section class="text-xs sm:text-base">
        <x-slot name="heading">
            @if ($isDemo)
                Welcome to the PRIMA Demo!
            @else
                How PRIMA Works
            @endif
        </x-slot>

        <x-slot name="headerEnd">
            @if (!$isDemo)
                <div class="text-xs text-gray-500">
                    {{ auth()->user()->created_at->setTimezone(auth()->user()->timezone)->format('M j g:ia') }}
                </div>
            @endif
        </x-slot>

        <div class="flex justify-center -mx-6 -mt-6 sm:hidden">
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
                            Availability Calendar</a>.
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
                        prima@primavip.co</a>
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
        <div class="flex flex-col gap-4 -mt-4">
            @foreach ($this->messages as $message)
                <x-filament::section>
                    <x-slot name="heading">
                        {{ $message->announcement->title }}
                    </x-slot>

                    <x-slot name="headerEnd">
                        <div class="text-xs text-gray-500">
                            {{ $message->created_at->setTimezone(auth()->user()->timezone)->format('M j g:ia') }}
                        </div>
                    </x-slot>

                    <div
                        class="flex flex-col gap-4 [&_a]:text-indigo-600 [&_a]:underline [&_a]:font-semibold text-xs sm:text-base">
                        {!! Illuminate\Mail\Markdown::parse($message->announcement->message) !!}

                        @if (isset($message->announcement->call_to_action_title, $message->announcement->call_to_action_url))
                            <x-filament::button tag="a" :href="$message->announcement->call_to_action_url" target="_blank">
                                {{ $message->announcement->call_to_action_title }}
                            </x-filament::button>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
