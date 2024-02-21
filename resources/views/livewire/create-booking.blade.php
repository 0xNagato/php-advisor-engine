<div class="min-h-screen antialiased bg-gradient-to-b from-white to-indigo-200 flex flex-col p-6">
    <div class="font-extrabold text-xl uppercase text-indigo-800">
        Prima
    </div>
    <div class="flex-grow flex flex-col pt-48 items-center gap-4" id="form">
        <h1 class="text-3xl font-bold">You're almost there!</h1>
        <h2 class="text-xl text-center">
            Complete the form guarantee your reservation
            at <strong class="font-semibold">{{ $booking->schedule->restaurant->restaurant_name }}</strong>
            at <strong class="font-semibold">{{ $booking->booking_at->format('g:i a') }}</strong>.
        </h2>
        <x-mary-form wire:submit="save">
            <div class="flex gap-2">
                <x-mary-input label="First Name" inline/>
                <x-mary-input label="Last Name" inline/>
            </div>
            <div class="flex gap-2">
                <x-mary-input label="Email Address" inline/>
                <x-mary-input label="Phone Number" inline/>
            </div>
            <div id="card-element"
                 class="input input-primary w-full flex flex-col justify-center">
                <!-- A Stripe Element will be inserted here. -->
            </div>

            <x-mary-button class="btn-primary text-white">Complete Reservation</x-mary-button>
        </x-mary-form>
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
