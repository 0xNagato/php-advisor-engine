<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col -m-6 divide-y">
            @foreach ($specialRequests as $specialRequest)
                <livewire:special-request.special-request-list-item :specialRequest="$specialRequest" :key="$specialRequest->id" />
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
