<x-filament-panels::page>
    <div class="bg-white px-6 pt-8 pb-8 shadow-sm ring-1 ring-gray-950/5 rounded-lg mx-4 max-w-md sm:mx-auto"
        data-cy="twoFactorModal">
        <div>
            <div class="font-semibold text-lg mb-4 text-center">
                {{ $this->heading }}
            </div>
        </div>
        <div class="mb-4">
            <p>
                Enter the 6-digit number that we sent to your phone number ending in *{{ $this->phoneNumber }}.
            </p>
        </div>

        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}
            <div>
                Didn't receive the code?
                <a href="#" class="underline" wire:click="regenerateCode">
                    Resend Code
                </a>
            </div>
            <div>
                <x-filament::button type="submit" class="w-full">
                    Submit
                </x-filament::button>
            </div>
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
