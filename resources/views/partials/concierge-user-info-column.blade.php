<dl>
    {{ $name }}
    <dt class="text-xs font-semibold">Date Joined:</dt>
    <dd>
        {{ $secured_at->format('D M j, Y') }}<br/>
    </dd>
    <dt class="text-xs font-semibold">Referred By:</dt>
    <dd>
        {{ $referrer_name }}<br/>
    </dd>
</dl>
