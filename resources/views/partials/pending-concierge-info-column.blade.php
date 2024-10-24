<dl>
    {{ $record->first_name . ' ' . $record->last_name }}
    @if (is_null($record->email))
        <dd>{{ $record->phone }}</dd>
    @else
        <dd>{{ $record->email }}</dd>
    @endif
</dl>
