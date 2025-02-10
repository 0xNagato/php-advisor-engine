<x-filament-panels::page>
    <x-filament::section>
        <x-slot:heading>
            Prime Booking Fees
        </x-slot:heading>
        <div class="p-4 mb-4 text-sm font-medium rounded-lg text-primary-600 bg-primary-50">
            <p class="flex items-center gap-2">
                <x-heroicon-m-information-circle class="flex-shrink-0 w-5 h-5" />
                To modify your booking fees, please contact your PRIMA account manager.
            </p>
        </div>
        <form wire:submit.prevent="saveBookingFeesForm">

            {{ $this->bookingFeesForm }}

            <x-filament::button type="submit" class="w-full mt-4" disabled>
                Update Prime Booking Fees
            </x-filament::button>
        </form>
    </x-filament::section>

    <x-filament::section>
        <x-slot:heading>
            Special Days
        </x-slot:heading>

        <form wire:submit.prevent="saveSpecialPricingForm" class="space-y-4">
            <p class="-mt-2 text-sm">
                If you'd like to increase or decrease the base price of a reservation on a special occasion, please do
                so here.
            </p>
            {{ $this->specialPricingForm }}

            <x-filament::button type="submit" class="w-full">
                Save Special Day
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
