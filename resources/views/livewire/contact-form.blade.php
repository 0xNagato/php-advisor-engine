<x-filament::modal id="contact-us-modal">
    <x-slot name="heading">
        Contact Us
    </x-slot>

    <form wire:submit="submit">
        {{ $this->form }}

        <div class="flex justify-end mt-4">
            <x-filament::button type="submit">
                Send Message
            </x-filament::button>
        </div>
    </form>
</x-filament::modal>
