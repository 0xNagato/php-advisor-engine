<x-filament-widgets::widget>
    @if ($SMSSent)
        <div class="flex flex-col gap-4 p-4 mb-4 bg-white rounded shadow text-sm">
            <p>Advise customer to check their phone for reservation payment link.</p>
            <p>Sending message to customer now.</p>
        </div>
    @endif

    <form wire:submit="handleSubmit" class="py-2">
        {{ $this->form }}

        @if ($booking->prime_time)
            <label class="flex items-center mt-4 mb-2 w-full text-xs">
                <input type="checkbox" name="prime_time_fee_confirmation_sms" required
                    class="text-indigo-600 rounded form-checkbox">
                <span class="ml-2 text-xs text-gray-700 font-bold">I have informed the guest that this fee is for a prime time reservation and is not applied towards their bill at the restaurant.</span>
            </label>
        @endif

        <x-filament::button type="submit" class="mt-2 w-full text-lg submit-btn">
            Send Payment Link to Client
        </x-filament::button>
    </form>
</x-filament-widgets::widget>
