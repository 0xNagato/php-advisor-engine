<x-filament-panels::page>
    <div class="relative mt-4 overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3">Concierge</th>
                    <th scope="col" class="px-4 py-3 font-bold text-center">Total</th>
                    @foreach ($weekRanges as $index => $weekRange)
                        <th scope="col" class="px-3 py-3 text-center">{{ $weekRange['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if (count($conciergeData) > 0)
                    @foreach ($conciergeData as $concierge)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="#" x-data="{}"
                                    x-on:click.prevent="$dispatch('open-modal', { id: 'view-concierge-{{ $concierge['id'] }}' })"
                                    class="cursor-pointer">
                                    <div class="font-medium text-gray-900 text-primary">{{ $concierge['name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $concierge['hotel'] }}</div>
                                </a>
                            </td>
                            <td
                                class="py-3 px-4 text-center font-bold {{ $concierge['totalBookings'] > 0 ? 'text-green-600' : '' }}">
                                {{ $concierge['totalBookings'] }}
                            </td>
                            @foreach ($weekRanges as $index => $weekRange)
                                <td
                                    class="py-3 px-3 text-center {{ isset($concierge['weeklyBookings'][$index]) && $concierge['weeklyBookings'][$index] > 0 ? 'text-green-600' : '' }}">
                                    {{ $concierge['weeklyBookings'][$index] ?? 0 }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ count($weekRanges) + 2 }}" class="py-5 text-center text-gray-500">
                            No concierges with bookings found in the past 6 weeks.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Modals rendered outside the table --}}
    @foreach ($conciergeData as $concierge)
        <x-filament::modal id="view-concierge-{{ $concierge['id'] }}" width="md">
            <x-slot name="heading">{{ $concierge['name'] }}</x-slot>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="block font-medium text-gray-500">Hotel/Company</span>
                        <span class="block">{{ $concierge['hotel'] }}</span>
                    </div>
                    <div>
                        <span class="block font-medium text-gray-500">Phone</span>
                        <span class="block">{{ $concierge['phone'] ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block font-medium text-gray-500">Email</span>
                        <span class="block">{{ $concierge['email'] ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block font-medium text-gray-500">Total Bookings</span>
                        <span class="block">{{ $concierge['totalBookings'] }}</span>
                    </div>
                </div>
            </div>

            <x-slot name="footerActions">
                <x-filament::button tag="a"
                    href="{{ route('filament.admin.resources.users.edit', ['record' => $concierge['user_id'] ?? '']) }}"
                    target="_blank" icon="heroicon-m-pencil-square" color="warning">
                    Edit
                </x-filament::button>

                <x-filament::button tag="a"
                    href="{{ route('filament.admin.resources.concierges.view', ['record' => $concierge['id']]) }}"
                    target="_blank" icon="heroicon-m-document-text" color="info">
                    Overview
                </x-filament::button>

                <x-filament::button tag="a"
                    href="{{ route('filament.admin.pages.booking-search', ['filters' => ['concierge_search' => $concierge['name']]]) }}"
                    target="_blank" icon="heroicon-m-calendar" color="success">
                    Bookings
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    @endforeach
</x-filament-panels::page>
