@php use Carbon\Carbon; @endphp
<div class="flex flex-col justify-center min-h-screen p-6 antialiased wavy-background">
    <div class="w-full text-3xl font-bold text-center leading-5 tracking-tight text-gray-950">
        PRIMA
    </div>
    <div class="dm-serif text-2xl p-2 text-center font-bold tracking-tight text-gray-950">
        Everybody Wins
        {{--<sup>&trade;</sup>--}}
    </div>
    <div class="flex flex-col items-center flex-grow max-w-4xl mx-auto mt-4">
        <div
            class="bg-white p-4 shadow-sm ring-1 ring-gray-950/5 rounded-lg mx-4 w-full flex flex-col gap-4">
            <h1 class="text-2xl font-bold text-center dm-serif">Confirm Special Request</h1>

            <div class="space-y-4 w-full">
                <div>
                    <p class="text-base font-medium text-gray-700">Customer Name: <span
                            class="text-gray-900">{{ $specialRequest->customer_first_name }} {{ $specialRequest->customer_last_name }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-base font-medium text-gray-700">Customer Phone: <span
                            class="text-gray-900">{{ $specialRequest->customer_phone }}</span></p>
                </div>
                <div>
                    <p class="text-base font-medium text-gray-700">Customer Email: <span
                            class="text-gray-900">{{ $specialRequest->customer_email }}</span></p>
                </div>
                <div>
                    <p class="text-base font-medium text-gray-700">Booking Date: <span
                            class="text-gray-900">{{ Carbon::parse($specialRequest->booking_date)->format('l, F jS, Y') }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-base font-medium text-gray-700">Booking Time: <span
                            class="text-gray-900">{{ Carbon::parse($specialRequest->booking_time)->format('g:ia') }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-base font-medium text-gray-700">Party Size: <span
                            class="text-gray-900">{{ $specialRequest->party_size }}</span></p>
                </div>
                <div>
                    <p class="text-base font-medium text-gray-700">Minimum Spend: <span
                            class="text-gray-900">{{ money($specialRequest->minimum_spend * 100, $specialRequest->restaurant->inRegion->currency) }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-base font-medium text-gray-700">Commission Requested: <span
                            class="text-gray-900">{{ $specialRequest->commission_requested_percentage }}%</span></p>
                </div>
                @if($specialRequest->special_request)
                    <div>
                        <p class="text-base font-medium text-gray-700">Special Request:
                            <span class="text-gray-900 font-light">
                                {!! nl2br(e($specialRequest->special_request)) !!}
                            </span>
                        </p>
                    </div>
                @endif

                <div>
                    <label for="message" class="block text-base font-medium text-gray-700">Message (optional):</label>
                    <textarea id="message" wire:model="message"
                              class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-base"></textarea>
                </div>
            </div>

            <div class="flex flex-col space-y-2 w-full">
                <button wire:click="confirmRequest"
                        onclick="confirm('Are you sure you want to confirm this request?') || event.stopImmediatePropagation()"
                        class="inline-flex justify-center items-center px-2 py-1.5 bg-indigo-600 border border-transparent rounded-md font-bold text-base text-white hover:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 active:bg-indigo-600 disabled:opacity-25 transition w-full">
                    Confirm Special Request
                </button>
                <button wire:click="denyRequest"
                        onclick="confirm('Are you sure you want to deny this request?') || event.stopImmediatePropagation()"
                        class="inline-flex justify-center items-center px-2 py-1.5 bg-gray-500 border border-transparent rounded-md font-bold text-base text-white hover:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring focus:ring-gray-200 active:bg-gray-600 disabled:opacity-25 transition w-full">
                    Deny Special Request
                </button>
            </div>
        </div>
    </div>
    <div class="flex items-end justify-center text-sm text-center mt-4">
        &copy; {{ date('Y') }} {{ config('app.name', 'PRIMA VIP') }}. All rights reserved.
    </div>
</div>
