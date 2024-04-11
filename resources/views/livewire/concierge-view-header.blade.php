<x-filament-widgets::widget>
    <div class="flex items-center">
        <div class="flex-grow flex flex-col">
            <div class="dm-serif font-bold text-2xl">
                {{ $concierge->user->name }}
            </div>
            <div>
                <span class="text-sm text-gray-500">{{ $concierge->hotel_name }}</span>
            </div>
        </div>

        <div>
            {{ $this->impersonateUser }}
            {{ $this->editConcierge }}
        </div>
    </div>
    {{--    <x-filament-actions::modals/>--}}
</x-filament-widgets::widget>
