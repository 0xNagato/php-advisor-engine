<x-filament-panels::page>
    {{ $this->tabbedForm }}

    {{--    <x-filament::section>--}}
    {{--        <x-slot name="heading">--}}
    {{--            Invite via Email--}}
    {{--        </x-slot>--}}

    {{--        <form wire:submit="sendInviteViaEmail">--}}
    {{--            {{ $this->emailForm }}--}}
    {{--            <x-filament::button type="submit" class="w-full mt-4">--}}
    {{--                Send Invitation Email--}}
    {{--            </x-filament::button>--}}
    {{--        </form>--}}
    {{--    </x-filament::section>--}}

    {{--    <x-filament::section>--}}
    {{--        <x-slot name="heading">--}}
    {{--            Invite via SMS--}}
    {{--        </x-slot>--}}

    {{--        <form wire:submit="sendInviteViaText">--}}
    {{--            {{ $this->textForm }}--}}
    {{--            <x-filament::button type="submit" class="w-full mt-4">--}}
    {{--                Send Invitation SMS--}}
    {{--            </x-filament::button>--}}
    {{--        </form>--}}
    {{--    </x-filament::section>--}}

    <livewire:referrals-table/>

    <div class="h-0">
        <x-filament-actions::modals/>
    </div>
</x-filament-panels::page>
