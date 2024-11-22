<x-layouts.app>
    <x-layouts.simple-wrapper>
        <div class="w-full">
            @if (config('app.bookings_enabled'))
                <livewire:booking.booking-checkout :booking="$booking" />
            @else
                <div class="text-center">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
                    </div>
                    <h3 class="mb-2 text-lg font-medium text-gray-900">Soon!</h3>
                    <p class="text-sm text-gray-500">
                        {{ config('app.bookings_disabled_message') }}
                    </p>
                </div>
            @endif
        </div>

        <div class="w-full mt-4">
            <livewire:booking.invoice-small :booking="$booking" />
        </div>
    </x-layouts.simple-wrapper>
    @include('partials.bookings-disabled-modal')
</x-layouts.app>
