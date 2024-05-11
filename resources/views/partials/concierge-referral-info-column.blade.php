<div>
    @if ($record->secured_at)
        <p>{{ $record->user->international_formatted_phone_number }}</p>
        <p>{{ $record->user->name }}</p>
    @else
        <p>{{ $record->phone }}</p>
        <p>{{ $record->name }}</p>
    @endif
</div>
