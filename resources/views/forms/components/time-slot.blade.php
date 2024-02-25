<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <!--suppress JSUnresolvedReference, BadExpressionStatementJS -->
    <div x-data="{
        state: $wire.$entangle('{{ $getStatePath() }}'),
        formatTime(time) {
            const date = new Date(time);
            return date.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true, timeZone: 'UTC' });
        }
    }" class="grid grid-cols-[24%_20%_auto] items-center">


        <label x-text="formatTime(state.start_time)" class="font-semibold" for="{{ $getStatePath() }}"></label>
        <x-filament::input.checkbox x-model="state.is_available" id="{{ $getStatePath() }}"/>


        <x-filament::input.wrapper prefix-icon="fas-people-group" class="flex-grow">
            <x-filament::input type="number" x-model="state.available_tables"/>
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>
