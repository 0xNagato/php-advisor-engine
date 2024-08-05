<x-filament-panels::page>
    <x-filament::section>
        <x-slot:heading>
            Prime Booking Fees
        </x-slot:heading>

        <form wire:submit.prevent="saveBookingFeesForm">
            {{ $this->bookingFeesForm }}

            <x-filament::button type="submit" class="w-full mt-4">
                Update Prime Booking Fees
            </x-filament::button>
        </form>
    </x-filament::section>

    <x-filament::section>
        <x-slot:heading>
            Special Days
        </x-slot:heading>

        <form wire:submit.prevent="saveSpecialPricingForm" class="space-y-4">
            <p class="text-sm -mt-2">
                If you’d like to increase or decrease the base price of a reservation on a special occasion, please do
                so here.
            </p>
            {{ $this->specialPricingForm }}

            <x-filament::button type="submit" class="w-full">
                Save Special Day
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
