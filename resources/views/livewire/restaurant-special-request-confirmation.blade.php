@php
    use Carbon\Carbon;
    use App\Enums\SpecialRequestStatus;
@endphp
<x-layouts.simple-wrapper>
    <div class="flex flex-col w-full gap-4 p-4 mx-4 bg-white rounded-lg shadow-sm ring-1 ring-gray-950/5">
        <h1 class="text-2xl tracking-tight text-center text-gray-950 dm-serif">
            Confirm Special Request
        </h1>

        <div class="w-full space-y-3 text-sm">
            <div class="relative">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-3 text-base font-semibold leading-6 text-gray-900 bg-white">
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
                        {{ money($this->minimumSpend * 100, $specialRequest->restaurant->inRegion->currency) }}
                    </span>
                </p>
            </div>
            <div>
                <p class="font-medium text-gray-700">
                    <span class="font-semibold">Commission Requested:</span>
                    <span class="text-black">
                        {{ $this->commissionRequestedPercentage }}%
                    </span>
                </p>
            </div>

            <div>
                <div class="font-medium text-gray-700">
                    <span class="font-semibold">Example Total Fee:</span>
                    <span class="text-black">
                        {{ money($this->restaurantTotalFee * 100, $specialRequest->restaurant->inRegion->currency) }}
                    </span>
                    <div class="mt-1 text-xs">
                        ({{ $this->commissionRequestedPercentage }}% Commission + 7% PRIMA Platform Fee)
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-3 text-base font-semibold leading-6 text-gray-900 bg-white">
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
            @if ($specialRequest->customer_email)
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Email:</span>
                        <span class="text-black">
                            {{ $specialRequest->customer_email }}
                        </span>
                    </p>
                </div>
            @endif

            @if ($specialRequest->special_request)
                <div>
                    <p class="font-medium text-gray-700">
                        <span class="font-semibold">Special Request:</span>
                        <span class="font-light text-black">
                            {!! nl2br(e($specialRequest->special_request)) !!}
                        </span>
                    </p>
                </div>
            @endif

            @if ($specialRequest->status === SpecialRequestStatus::Pending)
                @if ($showRequestChangesForm)
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-3 text-base font-semibold leading-6 text-gray-900 bg-white">
                                Request Changes
                            </span>
                        </div>
                    </div>

                    <p>
                        Any changes you submit will be sent back to the concierge. If the concierge and client accept
                        the changes, you will be notified.
                    </p>

                    {{ $this->requestChangesForm }}
                @endif

                @if (!$showRequestChangesForm)
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-3 text-base font-semibold leading-6 text-gray-900 bg-white">Approval</span>
                        </div>
                    </div>
                    {{ $this->approvalForm }}
                @endif
            @else
                <div class="relative">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="px-3 text-base font-semibold leading-6 text-gray-900 bg-white">Confirmation</span>
                    </div>
                </div>
                <p>
                    {{ $this->confirmationMessage }}
                </p>
            @endif
        </div>
    </div>

    @if (auth()->user()->hasRole('super_user') || app('impersonate')->isImpersonating())
        <x-filament::button color="gray" class="w-full mt-4" size="sm" icon="polaris-reset-icon"
            wire:click="resetStatus">
            Reset Status
        </x-filament::button>
    @endif
</x-layouts.simple-wrapper>
