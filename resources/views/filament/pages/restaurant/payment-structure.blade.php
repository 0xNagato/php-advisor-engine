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
            Daily Custom Pricing Override
        </x-slot:heading>

        <x-slot name="description">
            Define exceptional pricing rules for specific dates, allowing you to temporarily supersede the standard
            booking fees and offer tailored pricing for select events or promotions.
        </x-slot>

        <form wire:submit.prevent="saveSpecialPricingForm">
            {{ $this->specialPricingForm }}

            <x-filament::button type="submit" class="w-full mt-4">
                Update Custom Pricing Override
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
