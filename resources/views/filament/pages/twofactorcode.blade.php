<x-filament-panels::page>
    <div class="bg-white px-6 pt-8 pb-8 shadow-sm ring-1 ring-gray-950/5 rounded-lg mx-4 max-w-md sm:mx-auto"
        data-cy="twoFactorModal" wire:poll.10000ms="$refresh">
        <div>
            <div class="font-semibold text-lg mb-4 text-center">
                {{ $this->heading }}
            </div>
        </div>

        @if (!$formVisible)
            <p class="mb-4">
                We need to send a 6-digit code to your phone number ending in *{{ $this->phoneNumberSuffix }}.
            </p>
            <div class="mb-4">
                <x-filament::button wire:click="sendCode('sms')" class="w-full">
                    Send Code via SMS
                </x-filament::button>
            </div>

            <p class="mb-4">
                Or send to your email address {{ $this->emailSuffix }}.
            </p>
            <div class="mb-4">
                <x-filament::button wire:click="sendCode('email')" class="w-full">
                    Send Code via Email
                </x-filament::button>
            </div>
        @else
            <p class="mb-4">
                Enter the 6-digit number that we sent to your
                @if ($via === 'sms')
                    phone number ending in *{{ $this->phoneNumberSuffix }}.
                @else
                    email address {{ $this->emailSuffix }}.
                @endif
            </p>
            <x-filament-panels::form wire:submit="save">
                {{ $this->form }}
                <div>
                    @if (!$this->canResendCode)
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


                <p class="text-xs underline text-indigo-600 text-center">
                    <button type="button" wire:click="resetForm" class="hover:text-indigo-800">
                        Didn't receive the code? Send via {{ $via === 'sms' ? 'email' : 'SMS' }} instead.
                    </button>
                </p>

            </x-filament-panels::form>
        @endif
    </div>
</x-filament-panels::page>
