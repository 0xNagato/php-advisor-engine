<div class="min-h-screen antialiased bg-gradient-to-b from-white to-indigo-200 p-6 flex flex-col">
    <div class="max-w-lg mx-auto flex flex-col flex-grow">
        <div class="font-extrabold text-xl uppercase text-indigo-800">
            Prima
        </div>

        <div class="flex flex-col pt-24 items-center gap-4" id="form">
            <h1 class="text-3xl font-bold">You're almost there!</h1>
            <h2 class="text-xl text-center">
                Complete the form guarantee your reservation
                at <strong class="font-semibold">{{ $booking->schedule->restaurant->restaurant_name }}</strong>
                at <strong class="font-semibold">{{ $booking->booking_at->format('g:i a') }}</strong>.
            </h2>
            <x-mary-form wire:submit="save">
                <div class="flex gap-2">
                    <x-mary-input label="First Name" :label="false" placeholder="First Name"/>
                    <x-mary-input label="Last Name" :label="false" placeholder="Last Name"/>
                </div>
                <div class="flex gap-2">
                    <x-mary-input label="Email Address" :label="false" placeholder="Email Address"/>
                    <x-mary-input label="Phone Number" :label="false" placeholder="Phone Number"/>
                </div>
                <div id="card-element"
                     class="input input-primary w-full flex flex-col justify-center">
                    <!-- A Stripe Element will be inserted here. -->
                </div>

                <x-mary-button class="btn-primary text-white">Complete Reservation</x-mary-button>
            </x-mary-form>
        </div>

        <!-- Invoice -->
        <div class="flex-grow mt-6">
            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 flex flex-col my-2 w-full mx-auto">
                <h2 class="text-center font-bold text-xl uppercase mb-6">Receipt</h2>
                <div class="mb-4">
                    <span class="text-gray-600">Restaurant:</span>
                    <span class="float-right font-semibold">{{ $booking->schedule->restaurant->restaurant_name }}</span>
                </div>
                <div class="mb-4">
                    <span class="text-gray-600">Time:</span>
                    <span class="float-right font-semibold">{{ $booking->booking_at->format('g:i a') }}</span>
                </div>
                <div class="mb-4">
                    <span class="text-gray-600">Guest Count:</span>
                    <span class="float-right font-semibold">{{ $booking->guest_count }}</span>
                </div>
                <div class="mb-4">
                    <span class="text-gray-600">Amount Due:</span>
                    <span class="float-right font-semibold">{{ money($booking->total_fee) }}</span>
                </div>
            </div>
        </div>
        <!-- End of Invoice -->

    </div>
    <div class="text-center text-sm">
        &copy; {{ date('Y') }} {{ config('app.name', 'Prima') }}. All rights reserved.
    </div>
</div>


@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
@endpush
<script>
    var stripe = Stripe('{{ config('cashier.key') }}');
    var elements = stripe.elements();
    var card = elements.create('card');
    card.mount('#card-element');
</script>
