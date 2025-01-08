<x-filament-widgets::widget>
    @if ($booking)
        @if ($booking->hasActiveModificationRequest())
            <div class="p-4 text-sm text-center rounded-lg bg-primary-50 text-primary-600">
                <x-heroicon-m-clock class="w-6 h-6 mx-auto mb-2" />
                <p class="font-medium">Modification Request Pending</p>
                <p class="mt-1 text-primary-500">
                    There is already a pending modification request for this booking.
                    Please wait for the venue to respond.
                </p>
            </div>
        @else
            {{ $this->form }}

            <div class="mt-6 space-y-4">
                <div class="text-sm font-medium text-gray-500">
                    Available Time Slots
                </div>

                <div class="grid grid-cols-3 gap-2">
                    @foreach ($availableSlots as $slot)
                        <button wire:click="selectTimeSlot({{ $slot['id'] }})" @class([
                            'relative flex flex-col items-center justify-center h-[2.5rem] p-2 text-sm font-semibold leading-none rounded-lg',
                            'bg-info-400 text-white hover:bg-info-500' =>
                                !$slot['is_current'] && !$slot['is_selected'],
                            'bg-info-100' => $slot['is_current'],
                            'bg-info-200 text-info-800' => $slot['is_selected'],
                        ])
                            type="button">
                            <span class="text-base">{{ $slot['time'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="flex justify-end w-full mt-4">
                    <x-filament::button wire:click="submitModificationRequest" :disabled="!$this->hasChanges" class="w-full">
                        Submit Change Request
                    </x-filament::button>
                </div>
            </div>
        @endif
    @else
        <div class="text-sm text-gray-500">
            No booking selected
        </div>
    @endif

</x-filament-widgets::widget>
