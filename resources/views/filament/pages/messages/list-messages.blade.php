<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Welcome to PRIMA!
        </x-slot>

        <x-slot name="headerEnd">
            <div class="text-xs text-gray-500">
                {{ auth()->user()->created_at->format('M j h:ia') }}
            </div>
        </x-slot>
        <div class="flex flex-col gap-4">
            <p>
                We are excited to have you as a part of our team when PRIMA fully launches. Restaurants are being
                onboarded now and we expect to be fully functioning in the coming weeks.
            </p>
            <p>
                So that you may fully experience the potential of PRIMA, we have created a demo concierge account
                for you to explore.
            </p>
            @unless (Request::getHost() === 'demo.primavip.co')
                <p>
                    Please click the link below and you will be taken to our demo site where you can
                    try out the Reservation Hub as well as other features of PRIMA.
                </p>
                <x-filament::button tag="a"
                    href="https://demo.primavip.co/login?email=concierge@primavip.co&password=demo2024" target="_blank">
                    Access Demo Account
                </x-filament::button>
            @endunless

            <div>
                <p>Welcome aboard!</p>
                <p>We look forward to working with you.</p>
            </div>
        </div>
    </x-filament::section>
    @if ($messages->isNotEmpty())
        <div class="flex flex-col gap-0 divide-y bg-white shadow-lg rounded-lg -mt-4">

            @foreach ($this->messages as $message)
                <div class="p-4">
                    <a href="{{ route('filament.admin.resources.messages.view', ['record' => $message->id]) }}">
                        <div class="flex flex-row items-center">
                            <div class="w-2/12 sm:w-1/12 mr-2">
                                <x-filament::avatar src="{{ $message->announcement->sender->getFilamentAvatarUrl() }}"
                                    alt="User Avatar" size="w-12 h-12" />
                            </div>

                            <div class="w-10/12">
                                <div class="flex flex-row items-center">
                                    <span class="font-semibold mr-1">
                                        {{ $message->announcement->sender->name }}
                                    </span>
                                    @if (is_null($message->read_at))
                                        <x-heroicon-s-information-circle
                                            class="h-4 w-4 -mt-0.5 text-xs text-green-600" />
                                    @endif
                                    <span class="grow self-start text-xs text-right">
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
