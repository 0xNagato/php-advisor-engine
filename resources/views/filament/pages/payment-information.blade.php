<x-filament-panels::page>
    <p>Payments are made on the 15th of every month for earnings generated in the previous month.</p>
    <x-filament::section>
        <div class="text-sm text-black font-semibold pb-4">
            Select your preferred payment choice below:
        </div>
        <form wire:submit="save" class="flex flex-col gap-3">

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="payout_type" required>
                    <option>Select Payout Type</option>
                    @foreach($payoutOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            @if ($payout_type === 'PayPal' || $payout_type === 'Check')
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        required
                        wire:model="payout_name"
                        placeholder="{{ $payout_type === 'PayPal' ? 'PayPal Email' : 'Name or Company Name' }}"
                    />
                </x-filament::input.wrapper>
            @elseif($payout_type === 'IBAN')
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        required
                        wire:model="payout_name"
                        placeholder="IBAN"
                    />
                </x-filament::input.wrapper>
            @elseif($payout_type === 'Direct Deposit')
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        required
                        wire:model="routing_number"
                        placeholder="Routing Number"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        required
                        wire:model="account_number"
                        placeholder="Account Number"
                    />
                </x-filament::input.wrapper>

                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="account_type" required>
                        <option>Select Account Type</option>
                        <option value="checking">Checking</option>
                        <option value="savings">Savings</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            @endif


            <div class="text-right">
                <x-filament::button type="submit" form="submit" class="w-full">
                    Update Payment Info
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section>
        <x-filament::section.heading>
            Payment Address
        </x-filament::section.heading>
        <form wire:submit="save" class="mt-4">

            {{ $this->form }}

            <div class="text-right mt-4">
                <x-filament::button type="submit" form="submit" class="w-full">
                    Update Address
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section>

        <div class="text-sm text-black font-semibold pb-4">
            PRIMA donates 5% of proceeds to help feed the homeless.
            How much would you like to donate?
        </div>
        <form wire:submit="save" class="flex flex-col gap-3">

            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model="charity_percentage"
                    hint="Donation to Charity (% of Fee Collected)"
                />
                <x-slot name="suffix">
                    %
                </x-slot>
            </x-filament::input.wrapper>

            <div class="text-right">
                <x-filament::button type="submit" form="submit" class="w-full">
                    Update Charity Percentage
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
