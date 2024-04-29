<x-filament-widgets::widget>
    @if ($SMSSent)
        <div class="flex flex-col gap-4 p-4 mb-4 bg-white rounded shadow text-sm">
            <p>Advise customer to check their phone for reservation payment link.</p>
            <p>Sending message to customer now.</p>
        </div>
    @endif

    <form wire:submit="handleSubmit" class="py-2">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4 w-full">
            SMS Booking Link
        </x-filament::button>
    </form>
</x-filament-widgets::widget>
