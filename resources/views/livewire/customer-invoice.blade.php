<div class="p-6">
    <style>
        @media (max-width: 1024px) {
            .invoice-container {
                height: calc(100vh - 198px);
            }
        }
    </style>

    @if(!$download)
        <div class="flex gap-x-2 lg:mx-auto max-w-3xl mb-4">
            <x-filament::button color="indigo" class="w-1/2" size="sm" icon="gmdi-email-o"
                                wire:click="showEmailForm">
                Email Invoice
            </x-filament::button>
            <x-filament::button color="indigo" class="w-1/2" size="sm" icon="gmdi-file-download-o"
                                tag="a"
                                :href="route('customer.invoice.download', ['uuid' => $booking->uuid])"
            >
                Download PDF
            </x-filament::button>
        </div>


        @if(isset($emailOpen) && $emailOpen)
            <form wire:submit="emailInvoice" class="my-4 bg-gray-100 p-4 rounded-lg border max-w-3xl mx-auto">
                {{ $this->form }}
                <button type="submit"
                        class="bg-indigo-600 w-full text-white px-4 py-2 rounded-lg text-sm font-semibold mt-4">
                    Send Email
                </button>
            </form>
        @endif

    @endif

    <div
        class=" bg-white rounded-xl shadow sm:max-w-3xl lg:mx-auto lg:min-h-[11in] invoice-container flex flex-col
            ">
        <div class="relative overflow-hidden text-center bg-gray-800 min-h-32 rounded-t-xl">
            <!-- SVG Background Element -->
            <figure class="absolute inset-x-0 bottom-0 -mb-px ">
                <svg preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                     viewBox="0 0 1920 100.1">
                    <path fill="currentColor" class="fill-white" d="M0,0c0,0,934.4,93.4,1920,0v100.1H0L0,0z"></path>
                </svg>
            </figure>
            <!-- End SVG Background Element -->
        </div>

        <div class="relative z-10 -mt-12">
            <!-- Icon -->
            <span
                class="mx-auto flex justify-center items-center size-[62px] rounded-full border border-gray-200 bg-white text-gray-700 shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400">
            <svg class="flex-shrink-0 size-6" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                 fill="currentColor" viewBox="0 0 16 16">
                <path
                    d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27zm.217 1.338L2 2.118v11.764l.137.274.51-.51a.5.5 0 0 1 .707 0l.646.647.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.509.509.137-.274V2.118l-.137-.274-.51.51a.5.5 0 0 1-.707 0L12 1.707l-.646.647a.5.5 0 0 1-.708 0L10 1.707l-.646.647a.5.5 0 0 1-.708 0L8 1.707l-.646.647a.5.5 0 0 1-.708 0L6 1.707l-.646.647a.5.5 0 0 1-.708 0L4 1.707l-.646.647a.5.5 0 0 1-.708 0l-.509-.51z"/>
                <path
                    d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5zm8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5z"/>
            </svg>
        </span>
            <!-- End Icon -->
        </div>

        <!-- Body -->
        <div class="p-4 overflow-y-auto sm:p-7">
            <div class="text-center">
                <h3 class="text-xl font-bold leading-5 tracking-tight text-gray-950">
                    PRIMA
                </h3>
                <p class="text-sm text-gray-500">
                    Invoice #{{ $booking->id }}
                </p>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-2 gap-5 mt-5 sm:mt-10 sm:grid-cols-4">
                <div>
                    <span class="block text-xs text-gray-500 uppercase">Customer:</span>
                    <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    {{ $booking->guest_name }}
                </span>
                </div>

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Amount Paid:</span>
                    <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    {{ money($booking->total_with_tax_in_cents) }}
                </span>
                </div>
                <!-- End Col -->

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Date Paid:</span>
                    <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    {{ $booking->confirmed_at->format('M d, Y') }}
                </span>
                </div>
                <!-- End Col -->

                <div>
                    <span class="block text-xs text-gray-500 uppercase">Payment Method:</span>
                    <div class="flex items-center gap-x-2">

                        {{-- amex, diners, discover, eftpos_au, jcb, mastercard, unionpay, visa, or unknown --}}

                        @if($booking->stripe_charge->paymentMethodDetails->card->brand === 'visa')
                            <x-fab-cc-visa class="h-6 w-6"/>
                        @endif
                        @if($booking->stripe_charge->paymentMethodDetails->card->brand === 'mastercard')
                            <x-fab-cc-mastercard class="h-6 w-6"/>
                        @endif
                        @if($booking->stripe_charge->paymentMethodDetails->card->brand === 'amex')
                            <x-fab-cc-amex class="h-6 w-6"/>
                        @endif
                        <span
                            class="block text-sm font-medium text-gray-800 dark:text-gray-200"
                        >
                        ••••{{ $booking->stripe_charge->paymentMethodDetails->card->last4 }}
                    </span>
                    </div>
                </div>
                <!-- End Col -->
            </div>
            <!-- End Grid -->

            <div class="mt-5 sm:mt-10">
                <h4 class="text-xs font-semibold text-gray-800 uppercase dark:text-gray-200">Summary</h4>

                <ul class="flex flex-col mt-3">
                    <li
                        class="inline-flex items-center px-4 py-3 -mt-px text-sm text-gray-800 border gap-x-2 first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
                        <div class="flex items-center justify-between w-full">
                        <span>
                            {{ $booking->restaurant->restaurant_name }} ({{ $booking->guest_count }} guests)
                        </span>
                            <span>
                            {{ money($booking->total_fee) }}
                        </span>
                        </div>
                    </li>
                    <li
                        class="inline-flex items-center px-4 py-3 -mt-px text-sm text-gray-800 border gap-x-2 first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:border-gray-700 dark:text-gray-200">
                        <div class="flex items-center justify-between w-full">
                            <span>Tax ({{ $booking->tax * 100 }}%)</span>
                            <span>
                            {{ money($booking->tax_amount_in_cents) }}
                        </span>
                        </div>
                    </li>
                    <li
                        class="inline-flex items-center px-4 py-3 -mt-px text-sm font-semibold text-gray-800 border gap-x-2 bg-gray-50 first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-slate-800 dark:border-gray-700 dark:text-gray-200">
                        <div class="flex items-center justify-between w-full">
                            <span>Amount Paid</span>
                            <span>
                            {{ money($booking->total_with_tax_in_cents) }}
                        </span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <!-- End Body -->
        <!-- Footer -->
        <div class="text-xs text-gray-500 flex-grow flex items-end justify-center p-4">
            Contact information goes here 1-888-555-5555.
        </div>
    </div>
</div>
