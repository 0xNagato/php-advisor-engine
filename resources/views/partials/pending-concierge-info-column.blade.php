<dl>
    {{ $record->first_name.' '.$record->last_name }}
    @if (is_null($record->email))
        <dt class="text-xs font-semibold">Phone:</dt>
        <dd>{{ $record->phone }}</dd>
    @else
        <dt class="text-xs font-semibold">Email:</dt>
        <dd>{{ $record->email }}</dd>
    @endif
</dl>
