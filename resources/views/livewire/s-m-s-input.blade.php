<x-filament-widgets::widget>
    <form wire:submit="send">
        <p class="text-base font-semibold mb-4">
            Enter the SMS phone number of the customer to send them their booking link.
        </p>
        {{ $this->form }}
        <x-filament::button type="submit" form="submit" class="w-full mt-2">
            Send SMS
        </x-filament::button>
    </form>
</x-filament-widgets::widget>
