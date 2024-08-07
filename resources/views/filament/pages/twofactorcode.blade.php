<x-filament-panels::page>
    <div class="bg-white px-6 pt-8 pb-8 shadow-sm ring-1 ring-gray-950/5 rounded-lg mx-4 max-w-md sm:mx-auto"
         data-cy="twoFactorModal"
         wire:poll.10000ms="$refresh">
        <div>
            <div class="font-semibold text-lg mb-4 text-center">
                {{ $this->heading }}
            </div>
        </div>
        <div class="mb-4">
            @if(!$formVisible)
                <p>
                    We need to send a 6-digit code to your phone number ending in *{{ $this->phoneNumberSuffix }}.
                </p>
            @else
                <p>
                    Enter the 6-digit number that we sent to your phone number ending in *{{ $this->phoneNumberSuffix }}
                    .
                </p>
            @endif
        </div>

        @if(!$formVisible)
            <div class="mb-4">
                <x-filament::button wire:click="sendCode" class="w-full">
                    Send Code
                </x-filament::button>
            </div>
        @else
            <x-filament-panels::form wire:submit="save">
                {{ $this->form }}
                <div>
                    @if(!$this->canResendCode)
                        <p class="text-sm text-gray-600">
                            If you haven't received a code, the next one will be available
                            at {{ $this->formattedNextCodeAvailableAt }}
                        </p>
                    @else
                        <p>
                            Didn't receive the code?
                            <button type="button" class="text-primary-600 underline" wire:click="generateNewCode">
                                Resend Code
                            </button>
                        </p>
                    @endif
                </div>
                <div>
                    <x-filament::button type="submit" class="w-full">
                        Submit
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        @endif
    </div>
</x-filament-panels::page>
