<x-filament-widgets::widget>
    @if($hasSent)
        <div class="text-center">
            <p class="text-lg font-semibold">Thank you for your message!</p>
            <p class="mt-2">We will get back to you as soon as possible.</p>
        </div>
    @else
        <form wire:submit.prevent="send">
            {{ $this->form }}

            <x-filament::button type="submit" color="primary" class="w-full mt-4">
                {{ __('Send') }}
            </x-filament::button>
        </form>
    @endif
</x-filament-widgets::widget>
