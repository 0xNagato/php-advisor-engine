<x-layouts.app>
    <x-layouts.simple-wrapper>
        <div class="w-full">
            <livewire:booking.booking-checkout :booking="$booking" />
        </div>

        <div class="w-full mt-4">
            <livewire:booking.invoice-small :booking="$booking" />
        </div>
    </x-layouts.simple-wrapper>
</x-layouts.app>
