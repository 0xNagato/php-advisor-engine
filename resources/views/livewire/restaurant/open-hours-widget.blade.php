<div>
    <form wire:submit.prevent="saveHours"
          class="relative w-full mx-auto overflow-hidden text-sm font-normal bg-white border border-gray-200 rounded-lg shadow p-4">

        @foreach ($daysOfWeek as $day)
            <div class="mb-6">
                <div class="flex items-center justify-stretch gap-4">

                    <div class="flex items-center min-w-[4rem]">
                        <input id="{{ $day }}" type="checkbox"
                               wire:model.live="selectedDays.{{ $day }}"
                               class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="{{ $day }}"
                               class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ ucfirst(substr($day, 0, 3)) }}</label>
                    </div>

                    <div class="w-full flex-grow">
                        <label for="start-time-{{ $day }}" class="sr-only">Start time:</label>
                        <div class="relative">
                            <input type="time" id="start-time-{{ $day }}"
                                   {{$selectedDays[$day] ? 'required' : 'disabled' }}
                                   wire:model="startTimes.{{ $day }}"
                                   class="bg-gray-50 border leading-none border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full px-2 p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
                                   min="00:00" max="24:00" step="1800"/>
                        </div>
                        @error('startTimes.' . $day) <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                    </div>

                    <div class="w-full flex-grow">
                        <label for="end-time-{{ $day }}" class="sr-only">End time:</label>
                        <div class="relative">
                            <input type="time" id="end-time-{{ $day }}"
                                   {{$selectedDays[$day] ? 'required' : 'disabled' }}
                                   wire:model="endTimes.{{ $day }}"
                                   class="bg-gray-50 border leading-none border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full px-2 p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
                                   min="00:00" max="24:00" step="1800"/>
                        </div>
                        @error('endTimes.' . $day) <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                    </div>
                </div>
            </div>
        @endforeach

        <x-filament::button type="submit" class="w-full mt-2" wire:loading.attr="disabled">
            Save Business Hours
        </x-filament::button>

    </form>
</div>
