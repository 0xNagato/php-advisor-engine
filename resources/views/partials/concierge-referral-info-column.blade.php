<div>
    @if ($record->secured_at)
        <p>{{ $record->user->name }}</p>
        <p class="text-xs italic text-gray-500">{{ $record->user->international_formatted_phone_number }}</p>
    @else
        <p>{{ $record->name }}</p>
        <p class="text-xs italic text-gray-500">{{ $record->phone }}</p>
        <p class="text-xs italic text-gray-500">{{ $record->email }}</p>
    @endif
</div>
