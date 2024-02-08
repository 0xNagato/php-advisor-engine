<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
    <div
        x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }"
    >
        <div class="flex items-center mb-2 text-sm font-semibold">
            <div
                :class="{ 'text-gray-400': state.closed }"
                class="flex-grow"
                x-text="new Intl.DateTimeFormat('en-US', { weekday: 'long', month: 'short', day: 'numeric' }).format(new Date(state.date))"
            ></div>

            <div>
                <template x-if="state.closed">
                    <x-filament::link icon="heroicon-m-lock-open" size="xs" tag="button" @click="state.closed = false">
                        Open
                    </x-filament::link>
                </template>

                <template x-if="!state.closed">
                    <x-filament::link icon="heroicon-m-lock-closed" size="xs" tag="button" @click="state.closed = true">
                        Closed
                    </x-filament::link>
                </template>
            </div>
        </div>

        <div class=" flex space-x-2">
            <x-filament::input.wrapper class="w-full">
                <x-filament::input
                    x-model="state.startTime"
                    step="1800"
                    type="time"
                    x-bind:disabled="state.closed"
                />
            </x-filament::input.wrapper>

            <x-filament::input.wrapper class="w-full">
                <x-filament::input
                    x-model="state.endTime"
                    step="1800"
                    type="time"
                    x-bind:disabled="state.closed"
                />
            </x-filament::input.wrapper>
        </div>


    </div>
</x-dynamic-component>
