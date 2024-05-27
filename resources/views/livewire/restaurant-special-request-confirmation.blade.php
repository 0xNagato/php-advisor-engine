@php use Carbon\Carbon; @endphp

<div class="flex min-h-screen flex-col justify-center p-6 antialiased wavy-background">
    <div class="w-full text-center text-3xl font-bold leading-5 tracking-tight text-gray-950">
        PRIMA
    </div>
    <div class="p-2 text-center text-2xl font-bold tracking-tight dm-serif text-gray-950">
        Everybody Wins
    </div>
    <div class="mx-auto mt-4 flex w-full max-w-lg flex-grow flex-col items-center">
        <div class="mx-4 flex w-full flex-col gap-4 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
            <h1 class="text-center text-2xl font-bold tracking-tight text-gray-950 dm-serif">
                Confirm Special Request
            </h1>

            <div class="w-full text-sm space-y-3">
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Customer Name:</span>
                        <span class="text-black">
                            {{ $specialRequest->customer_first_name }} {{ $specialRequest->customer_last_name }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Customer Phone:</span>
                        <span class="text-black">
                            {{ $specialRequest->customer_phone }}
                        </span>
                    </p>
                </div>
                @if($specialRequest->customer_email)
                    <div>
                        <p class="font-medium text-gray-700">
                            <span class="font-semibold">Customer Email:</span>
                            <span class="text-black">
                            {{ $specialRequest->customer_email }}
                        </span>
                        </p>
                    </div>
                @endif
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Booking Date:</span>
                        <span class="text-black">
                            {{ Carbon::parse($specialRequest->booking_date)->format('l, F jS, Y') }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Booking Time:</span>
                        <span class="text-black">
                            {{ Carbon::parse($specialRequest->booking_time)->format('g:ia') }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Party Size:</span>
                        <span class="text-black">
                            {{ $specialRequest->party_size }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Minimum Spend:</span>
                        <span class="text-black">
                            {{ money($specialRequest->minimum_spend * 100, $specialRequest->restaurant->inRegion->currency) }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Commission Requested:</span>
                        <span class="text-black">
                            {{ $specialRequest->commission_requested_percentage }}%
                        </span>
                    </p>
                </div>
                @if($specialRequest->special_request)
                    <div>
                        <p class="font-medium text-gray-700">
                            <span class="font-semibold">Special Request:</span>
                            <span class="font-light text-black">
                                {!! nl2br(e($specialRequest->special_request)) !!}
                            </span>
                        </p>
                    </div>
                @endif

                <div class="relative">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-base font-semibold leading-6 text-gray-900">Approval</span>
                    </div>
                </div>


                <div>
                    <label for="message" class="block font-medium text-gray-700">
                        Message (optional):
                    </label>
                    <textarea id="message" wire:model="message"
                              class="mt-1 block w-full rounded-md border border-gray-300 p-2 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <div class="flex w-full flex-col space-y-2">
                <button
                    wire:click="confirmRequest"
                    onclick="confirm('Are you sure you want to confirm this request?') || event.stopImmediatePropagation()"
                    class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-2 text-base font-bold text-white transition py-1.5 hover:bg-indigo-700 focus:border-indigo-700 focus:outline-none focus:ring focus:ring-indigo-200 active:bg-indigo-600 disabled:opacity-25"
                >
                    Confirm Special Request
                </button>
                <button
                    wire:click="denyRequest"
                    onclick="confirm('Are you sure you want to deny this request?') || event.stopImmediatePropagation()"
                    class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-gray-500 px-2 text-base font-bold text-white transition py-1.5 hover:bg-gray-700 focus:border-gray-700 focus:outline-none focus:ring focus:ring-gray-200 active:bg-gray-600 disabled:opacity-25"
                >
                    Deny Special Request
                </button>
            </div>
        </div>
    </div>
    <div class="mt-4 flex items-end justify-center text-center text-sm">
        &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
    </div>
</div>
