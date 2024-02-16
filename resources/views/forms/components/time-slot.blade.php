<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
    <div
        x-data="{ state: $wire.$entangle('{{ $getStatePath() }}'), formatTime(time) {
            let [hour, minute] = time.split(':');
            let date = new Date();
            date.setHours(hour, minute);
            return date.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        } }"
        class="flex items-center space-x-4 text-xs"
    >


        <label x-text="formatTime(state.start_time)" class="font-semibold flex-grow" for="{{ $getStatePath() }}">
        </label>
        <x-filament::input.checkbox x-model="state.is_available" id="{{ $getStatePath() }}"/>
        <x-filament::input.wrapper prefix-icon="heroicon-s-user-circle" class="flex-grow">
            <x-filament::input
                type="number"
                x-model="state.available_tables"
            />
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>
