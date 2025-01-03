<div>
    <div class="mb-4 text-xs font-semibold capitalize">Earnings Breakdown</div>
    <div class="grid grid-cols-3 gap-2 pb-4 mb-4 text-xs border-b">
        @foreach ($groupedEarnings as $earning)
            <div class="truncate" title="{{ $earning['user']->name ?? 'Unknown User' }}">
                @php
                    $url = match ($earning['type']) {
                        'venue_paid', 'venue' => \App\Filament\Resources\VenueResource::getUrl('view', ['record' => $earning['user']->venue->id ?? null]),
                        'concierge_bounty', 'concierge_referral_1', 'concierge_referral_2', 'concierge'
                            => \App\Filament\Resources\ConciergeResource::getUrl('view', ['record' => $earning['user']->concierge->id ?? null]),
                        'partner', 'partner_concierge', 'partner_venue'
                            => \App\Filament\Resources\PartnerResource::getUrl('view', ['record' => $earning['user']->partner->id ?? null]),
                        default => null,
                    };
                @endphp
                @if ($url)
                    <a href="{{ $url }}" class="text-indigo-600 hover:underline">
                        {{ $earning['user']->name ?? 'Unknown User' }}
                    </a>
                @else
                    {{ $earning['user']->name ?? 'Unknown User' }}
                @endif
            </div>
            <div title="{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $earning['type'])) }}">
                {{ match ($earning['type']) {
                    'concierge_referral_1' => 'Con. Ref. 1',
                    'concierge_referral_2' => 'Con. Ref. 2',
                    'partner_concierge' => 'Partner Con.',
                    'partner_venue' => 'Partner Venue',
                    default => \Illuminate\Support\Str::title(str_replace('_', ' ', $earning['type'])),
                } }}
            </div>
            <div class="text-right">
                {{ money($earning['amount'], $currency) }}
            </div>
        @endforeach

        <div class="col-span-2">PRIMA Earnings:</div>
        <div class="text-right">
            {{ money($platformEarnings, $currency) }}
        </div>
    </div>

    <div class="grid grid-cols-3 gap-2 text-xs font-semibold">
        <div class="col-span-2">Total Amount:</div>
        <div class="text-right">
            {{ money($totalWithTax, $currency) }}
        </div>
    </div>
</div>
