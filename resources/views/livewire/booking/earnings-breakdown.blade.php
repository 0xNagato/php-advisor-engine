@php
    use App\Filament\Resources\VenueResource;
    use App\Filament\Resources\ConciergeResource;
    use App\Filament\Resources\PartnerResource;
    use App\Enums\EarningType;
    use App\Models\User;
    use Illuminate\Support\Str;
@endphp
<div>
    <div class="mb-4 text-xs font-semibold capitalize">Earnings Breakdown</div>
    <div class="grid grid-cols-3 gap-2 pb-4 mb-4 text-xs border-b">
        @foreach ($groupedEarnings as $earning)
            <div class="truncate" title="{{ $earning['user']->name ?? 'Unknown User' }}">
                @php
                    /** @var User $user */
                    $user = $earning['user'];

                    /** @var EarningType $type */
                    $type = $earning['type'];

                    $url = match ($type) {
                        'venue_paid', 'venue' => $user->venue?->id
                            ? VenueResource::getUrl('view', [$user->venue->id])
                            : null,

                        'concierge_bounty', 'concierge_referral_1', 'concierge_referral_2', 'concierge' => $user
                            ->concierge?->id
                            ? ConciergeResource::getUrl('view', [$user->concierge->id])
                            : null,

                        'partner', 'partner_concierge', 'partner_venue' => $user->partner?->id
                            ? PartnerResource::getUrl('view', [$user->partner->id])
                            : null,

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
            <div title="{{ Str::title(str_replace('_', ' ', $earning['type'])) }}">
                {{ match ($earning['type']) {
                    'concierge_referral_1' => 'Con. Ref. 1',
                    'concierge_referral_2' => 'Con. Ref. 2',
                    'partner_concierge' => 'Partner Con.',
                    'partner_venue' => 'Partner Venue',
                    default => Str::title(str_replace('_', ' ', $earning['type'])),
                } }}
            </div>
            <div class="text-right">
                {{ money($earning['amount'], $currency) }}
            </div>
        @endforeach
        {{--
        <div class="col-span-2">PRIMA Earnings:</div>
        <div class="text-right">
            {{ money($platformEarnings, $currency) }}
        </div>
        --}}
        <div class="col-span-2">PRIMA Revenue:</div>
        <div class="text-right">
            {{ money($grossAmount, $currency) }}
        </div>
    </div>

    <div class="grid grid-cols-3 gap-2 text-xs font-semibold">
        <div class="col-span-2">Total Amount:</div>
        <div class="text-right">
            {{ money($totalWithTax, $currency) }}
        </div>
    </div>
</div>
