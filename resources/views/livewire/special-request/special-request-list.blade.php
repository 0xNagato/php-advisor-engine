<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col -m-6 divide-y">
            @forelse ($specialRequests as $specialRequest)
                <livewire:special-request.special-request-list-item :specialRequest="$specialRequest" :key="$specialRequest->id" />
            @empty
                <div class="p-4 text-sm text-center text-gray-500">
                    {{ __('No special requests found.') }}
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
