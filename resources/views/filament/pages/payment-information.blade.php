<x-filament-panels::page>
    <p>Payments are made on the 15th of every month for earnings generated in the previous month.</p>
    <x-filament::section>
        <form wire:submit="save" class="flex flex-col gap-3">

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="payout_type">
                    <option>Select Payout Type</option>
                    @foreach($payoutOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            @if ($payout_type === 'PayPal' || $payout_type === 'Venmo')
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="payout_name"
                        placeholder="{{ $payout_type === 'PayPal' ? 'PayPal Email' : 'Venmo Username' }}"
                    />
                </x-filament::input.wrapper>
            @elseif($payout_type === 'ACH')
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="routing_number"
                        placeholder="Routing Number"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="account_number"
                        placeholder="Account Number"
                    />
                </x-filament::input.wrapper>

                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="account_type">
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
</x-filament-panels::page>
