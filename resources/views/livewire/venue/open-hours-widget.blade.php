<div>
    <form wire:submit.prevent="saveHours"
          class="relative w-full mx-auto overflow-hidden text-sm font-normal bg-white border border-gray-200 rounded-lg shadow p-4">

        @foreach ($daysOfWeek as $day)
            <div class="mb-6 relative">
                <!-- Parent Checkbox -->
                <div class="flex items-center">

                    <!-- Checkbox -->
                    <input id="{{ $day }}" type="checkbox" wire:model.live="selectedDays.{{ $day }}"
                           class="z-10 w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 relative">

                    <!-- Label -->
                    <label for="{{ $day }}" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                        {{ ucfirst(substr($day, 0, 3)) }}
                    </label>
                </div>

                <!-- Time Block Section -->
                <div class="mt-2 pl-8 space-y-4">
                    @forelse ($openingHours[$day] ?? [] as $index => $block)
                        <div class="flex items-center gap-4">

                            <!-- Block: Start Time -->
                            <x-form.TimeInputComponent
                                id="start-time-{{ $day }}-{{ $index }}"
                                label="Start time:"
                                model="openingHours.{{ $day }}.{{ $index }}.start_time"
                                error-key="openingHours.{{ $day }}.{{ $index }}.start_time"
                            />

                            <!-- Block: End Time -->
                            <x-form.TimeInputComponent
                                id="end-time-{{ $day }}-{{ $index }}"
                                label="End time:"
                                model="openingHours.{{ $day }}.{{ $index }}.end_time"
                                error-key="openingHours.{{ $day }}.{{ $index }}.end_time"
                            />

                            <!-- Remove Block Button -->
                            {{ ($this->deleteAction)(['day' => $day, 'index'=>$index]) }}
                        </div>
                    @empty
                        <!-- If No Opening Hours -->
                        <div class="flex items-center gap-4 relative">
                            <!-- Horizontal Line -->
                            <span class="absolute top-[50%] left-[10px] h-[2px] w-[20px] bg-gray-300"></span>

                            <!-- Block: Empty Start Time -->
                            <x-form.TimeInputComponent
                                id="start-time-{{ $day }}-0"
                                label="Start time:"
                                model="openingHours.{{ $day }}.0.start_time"
                                error-key="openingHours.{{ $day }}.0.start_time"
                            />

                            <!-- Block: Empty End Time -->
                            <x-form.TimeInputComponent
                                id="end-time-{{ $day }}-0"
                                label="End time:"
                                model="openingHours.{{ $day }}.0.end_time"
                                error-key="openingHours.{{ $day }}.0.end_time"
                            />
                        </div>
                    @endforelse

                    <!-- Add Time Block Button -->
                    <div class="mt-2">
                        <button type="button" wire:click="addTimeBlock('{{ $day }}')"
                                class="w-full text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 px-4 py-2">
                            <x-heroicon-m-plus-circle class="w-6 h-6 mr-2 inline-block" />
                            Add Time Block
                        </button>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Submit Button -->
        <div class="flex justify-end mt-6">
            <button type="submit"
                    class="px-4 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Save Hours
            </button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
