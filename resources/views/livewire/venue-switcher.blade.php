<div>
    @if ($venues?->count() > 0)
        <x-filament::dropdown placement="bottom-end" class="filament-venue-switcher" teleport>
            <x-slot name="trigger">
                <button type="button"
                    class="flex items-center whitespace-nowrap gap-2 pl-2 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <span>{{ auth()->user()->currentVenueGroup()?->currentVenue(auth()->user())?->name ?? 'Select Venue' }}</span>
                    <x-heroicon-m-chevron-down class="w-4 h-4 text-indigo-600" />
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                @foreach ($venues as $venue)
                    <x-filament::dropdown.list.item wire:click="switchVenue({{ $venue->id }})"
                        wire:loading.attr="disabled" :color="auth()->user()->currentVenueGroup()?->currentVenue(auth()->user())?->id ===
                        $venue->id
                            ? 'primary'
                            : 'gray'" @class([
                                'font-semibold' =>
                                    auth()->user()->currentVenueGroup()?->currentVenue(auth()->user())
                                        ?->id === $venue->id,
                                'text-xs !important' => true,
                            ])>
                        {{ $venue->name }}
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    @endif
</div>
