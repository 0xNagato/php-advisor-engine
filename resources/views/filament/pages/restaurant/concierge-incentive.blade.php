<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit.prevent="saveForm" class="space-y-4">
            <p>
                The PRIMA Concierge Network can help you fill your dining rooms when they aren’t full by recommending
                your restaurant to diners.
            </p>

            <p>
                You may choose to provide a bounty for all non-prime reservations booked by PRIMA Concierges.
            </p>

            <p>
                Please enter the amount you’d like to pay concierges per diner
            </p>

            {{ $this->form }}

            @if($this->data['non_prime_type'] === 'paid')
                <p>
                    PRIMA will bill your account the incentive amount plus 7%.
                </p>
            @endif

            <x-filament::button type="submit" class="w-full mt-4">
                Update Non-Prime Booking Fees
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
