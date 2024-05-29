<dl>
    {{ $record->name }}
    <dt class="text-xs font-semibold">Date Joined:</dt>
    <dd>
        {{ $record->secured_at->format('D M j, Y') }}<br />
    </dd>
    <dt class="text-xs font-semibold">Referred By:</dt>
    <dd>
        {{ $record->referrer?->name ?? '-' }}<br />
    </dd>
</dl>
