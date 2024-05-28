@php use Carbon\Carbon; @endphp
<x-layouts.simple-wrapper>
    <div class="mx-4 flex w-full flex-col gap-4 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
        <h1 class="text-center text-2xl font-bold tracking-tight text-gray-950 dm-serif">
            Confirm Special Request
        </h1>

        <div class="w-full text-sm space-y-3">
            <div class="relative">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-white px-3 text-base font-semibold leading-6 text-gray-900">
                        Request Info
                    </span>
                </div>
            </div>

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


            @if($showRequestChangesForm)
                <div class="relative">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-base font-semibold leading-6 text-gray-900">
                            Request Changes
                        </span>
                    </div>
                </div>

                {{ $this->requestChangesForm }}
            @else
                {{ $this->showRequestChangesFormAction }}
            @endif


            <div class="relative">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-base font-semibold leading-6 text-gray-900">
                            Customer Details
                        </span>
                </div>
            </div>

            <div>
                <p class="font-medium text-gray-700">
                    <span class="font-semibold">Name:</span>
                    <span class="text-black">
                        {{ $specialRequest->customer_first_name }} {{ $specialRequest->customer_last_name }}
                    </span>
                </p>
            </div>
            <div>
                <p class="font-medium text-gray-700">
                    <span class="font-semibold">Phone:</span>
                    <span class="text-black">
                        {{ $specialRequest->customer_phone }}
                    </span>
                </p>
            </div>
            @if($specialRequest->customer_email)
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Email:</span>
                        <span class="text-black">
                            {{ $specialRequest->customer_email }}
                        </span>
                    </p>
                </div>
            @endif

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
            
            @if(!$showRequestChangesForm)
                <div class="relative">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-base font-semibold leading-6 text-gray-900">Approval</span>
                    </div>
                </div>
                {{ $this->approvalForm }}
            @endif
        </div>
    </div>
</x-layouts.simple-wrapper>
