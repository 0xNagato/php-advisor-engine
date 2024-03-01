<x-layouts.app>
    @pushOnce('scripts')
        <script src="https://js.stripe.com/v3/"></script>
    @endpushonce

    <div id="card-element" class="flex flex-col justify-center w-full input input-primary">
        <!-- A Stripe Element will be inserted here. -->
    </div>

    <script>
        const stripe = Stripe('{{ config('cashier.key') }}');

        function cardElement() {
            const elements = stripe.elements();
            const card = elements.create('card');
            card.mount('#card-element');
        }

        function paymentElement() {
            const options = {
                mode: 'setup',
                currency: 'usd',
            };
            const elements = stripe.elements(options);
            const paymentElement = elements.create('payment', options);
            paymentElement.mount('#payment-element');
        }

        cardElement();
    </script>
</x-layouts.app>
