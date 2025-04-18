@php
    use App\Filament\Resources\VenueResource;
    use App\Filament\Resources\ConciergeResource;
    use App\Filament\Resources\PartnerResource;
    use App\Enums\EarningType;
    use App\Models\User;
    use App\Constants\BookingPercentages;
    use Illuminate\Support\Str;

    if ($booking->is_prime) {
        // Prime booking calculations
        $initialPayouts = $groupedEarnings
            ->filter(fn($e) => in_array($e['type'], ['venue', 'venue_paid', 'concierge']))
            ->sum('amount');

        $grossAmount = $totalWithTax - $initialPayouts;

        $partnerAndReferralTotal = $groupedEarnings
            ->filter(
                fn($e) => in_array($e['type'], [
                    'partner_venue',
                    'partner_concierge',
                    'concierge_referral_1',
                    'concierge_referral_2',
                ]),
            )
            ->sum('amount');

        $primaShare = $grossAmount - $partnerAndReferralTotal;
    } else {
        // Non-prime booking calculations
        $fee = $booking->venue->non_prime_fee_per_head * $booking->guest_count;
        $venueFee = $fee * 100; // Convert to cents
        $processingFee = $venueFee * (BookingPercentages::NON_PRIME_PROCESSING_FEE_PERCENTAGE / 100);
        $totalFee = $venueFee + $processingFee;

        // Calculate earnings
        $conciergeEarnings = $venueFee * (BookingPercentages::NON_PRIME_CONCIERGE_PERCENTAGE / 100);
        $remainderNonPrime = $venueFee - $conciergeEarnings; // Pool is venue fee minus concierge bounty

        $partnerAndReferralTotal = $groupedEarnings
            ->filter(
                fn($e) => in_array($e['type'], [
                    'partner_venue',
                    'partner_concierge',
                    'concierge_referral_1',
                    'concierge_referral_2',
                ]),
            )
            ->sum('amount');
        $primaShare = $remainderNonPrime - $partnerAndReferralTotal + $processingFee; // Add processing fee to PRIMA's share
    }
@endphp
<div>
    @if ($booking->is_prime)
        <!-- Prime Booking Breakdown -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Prime Booking Breakdown</h2>

            <div class="mb-8">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">INCOME</h3>
                <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                    <span class="text-sm text-gray-600">Customer Payment Received by PRIMA:</span>
                    <span class="text-sm font-medium">{{ money($totalWithTax, $currency) }}</span>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">INITIAL PAYOUTS BY PRIMA
                </h3>
                @foreach ($groupedEarnings as $earning)
                    @if (in_array($earning['type'], ['venue', 'venue_paid', 'concierge']))
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">
                                @php
                                    /** @var User $user */
                                    $user = $earning['user'];
                                    $url = match ($earning['type']) {
                                        'venue_paid', 'venue' => $user->venue?->id
                                            ? VenueResource::getUrl('view', [$user->venue->id])
                                            : null,
                                        'concierge' => $user->concierge?->id
                                            ? ConciergeResource::getUrl('view', [$user->concierge->id])
                                            : null,
                                        default => null,
                                    };
                                @endphp
                                @if ($url)
                                    <a href="{{ $url }}" class="text-indigo-600 hover:underline">
                                        To {{ $earning['user']->name ?? 'Unknown User' }}
                                    </a>
                                @else
                                    To {{ $earning['user']->name ?? 'Unknown User' }}
                                @endif
                            </span>
                            <span class="text-sm font-medium">{{ money($earning['amount'], $currency) }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mb-8">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">OPERATIONAL REMAINDER</h3>
                <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                    <span class="text-sm text-gray-600">Funds Remaining Held by PRIMA:</span>
                    <span
                        class="text-sm font-semibold bg-blue-100 text-blue-800 px-2 py-0.5 rounded">{{ money($grossAmount, $currency) }}</span>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">DISTRIBUTION OF REMAINDER
                </h3>
                @foreach ($groupedEarnings as $earning)
                    @if (in_array($earning['type'], ['partner_venue', 'partner_concierge', 'concierge_referral_1', 'concierge_referral_2']))
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">
                                @php
                                    /** @var User $user */
                                    $user = $earning['user'];
                                    $url = match ($earning['type']) {
                                        'partner_venue' => $user->partner?->id
                                            ? PartnerResource::getUrl('view', [$user->partner->id])
                                            : null,
                                        'partner_concierge' => $user->partner?->id
                                            ? PartnerResource::getUrl('view', [$user->partner->id])
                                            : null,
                                        'concierge_referral_1', 'concierge_referral_2' => $user->concierge?->id
                                            ? ConciergeResource::getUrl('view', [$user->concierge->id])
                                            : null,
                                        default => null,
                                    };
                                @endphp
                                @if ($url)
                                    <a href="{{ $url }}" class="text-indigo-600 hover:underline">
                                        {{ match ($earning['type']) {
                                            'concierge_referral_1' => 'Concierge Referral 1 (10%)',
                                            'concierge_referral_2' => 'Concierge Referral 2 (5%)',
                                            'partner_concierge' => 'Partner (Concierge Referrer)',
                                            'partner_venue' => 'Partner (Venue Referrer)',
                                            default => Str::title(str_replace('_', ' ', $earning['type'])),
                                        } }}
                                    </a>
                                @else
                                    {{ match ($earning['type']) {
                                        'concierge_referral_1' => 'Concierge Referral 1 (10%)',
                                        'concierge_referral_2' => 'Concierge Referral 2 (5%)',
                                        'partner_concierge' => 'Partner (Concierge Referrer)',
                                        'partner_venue' => 'Partner (Venue Referrer)',
                                        default => Str::title(str_replace('_', ' ', $earning['type'])),
                                    } }}
                                @endif
                            </span>
                            <span class="text-sm font-medium">{{ money($earning['amount'], $currency) }}</span>
                        </div>
                    @endif
                @endforeach
                <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                    <span class="text-sm font-medium text-gray-600">PRIMA Share from Remainder:</span>
                    <span class="text-sm font-semibold">{{ money($primaShare, $currency) }}</span>
                </div>
            </div>

            <div class="mt-8">
                <p class="text-xs font-semibold text-gray-500 text-center uppercase tracking-wider mb-4">FINAL NET
                    POSITION</p>
                <div class="space-y-2">
                    @foreach ($groupedEarnings as $earning)
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">
                                @php
                                    /** @var User $user */
                                    $user = $earning['user'];
                                    $url = match ($earning['type']) {
                                        'venue_paid', 'venue' => $user->venue?->id
                                            ? VenueResource::getUrl('view', [$user->venue->id])
                                            : null,
                                        'concierge' => $user->concierge?->id
                                            ? ConciergeResource::getUrl('view', [$user->concierge->id])
                                            : null,
                                        'partner_venue', 'partner_concierge' => $user->partner?->id
                                            ? PartnerResource::getUrl('view', [$user->partner->id])
                                            : null,
                                        'concierge_referral_1', 'concierge_referral_2' => $user->concierge?->id
                                            ? ConciergeResource::getUrl('view', [$user->concierge->id])
                                            : null,
                                        default => null,
                                    };
                                @endphp
                                @if ($url)
                                    <a href="{{ $url }}" class="text-indigo-600 hover:underline">
                                        {{ match ($earning['type']) {
                                            'venue_paid', 'venue' => $user->name . ' (Venue) Net:',
                                            'concierge' => $user->name . ' (Concierge) Net:',
                                            'concierge_referral_1' => $user->name . ' (Referral 1) Net:',
                                            'concierge_referral_2' => $user->name . ' (Referral 2) Net:',
                                            'partner_concierge' => $user->name . ' (Partner Concierge) Net:',
                                            'partner_venue' => $user->name . ' (Partner Venue) Net:',
                                            default => $user->name . ' Net:',
                                        } }}
                                    </a>
                                @else
                                    {{ match ($earning['type']) {
                                        'venue_paid', 'venue' => $user->name . ' (Venue) Net:',
                                        'concierge' => $user->name . ' (Concierge) Net:',
                                        'concierge_referral_1' => $user->name . ' (Referral 1) Net:',
                                        'concierge_referral_2' => $user->name . ' (Referral 2) Net:',
                                        'partner_concierge' => $user->name . ' (Partner Concierge) Net:',
                                        'partner_venue' => $user->name . ' (Partner Venue) Net:',
                                        default => $user->name . ' Net:',
                                    } }}
                                @endif
                            </span>
                            <span
                                class="text-sm font-semibold text-gray-800">+{{ money($earning['amount'], $currency) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600">PRIMA Net:</span>
                        <span class="text-sm font-semibold text-gray-800">+{{ money($primaShare, $currency) }}</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Non-Prime Booking Breakdown -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Non-Prime Booking Breakdown</h2>

            <div class="mb-8">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">INCOME (PAID BY VENUE TO
                    PRIMA)</h3>
                <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                    <span class="text-sm text-gray-600">Venue Fee (Operational Fund):</span>
                    <span class="text-sm font-medium">{{ money($venueFee, $currency) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                    <span class="text-sm text-gray-600">Processing Fee
                        ({{ BookingPercentages::NON_PRIME_PROCESSING_FEE_PERCENTAGE }}%):</span>
                    <span class="text-sm font-medium">{{ money($processingFee, $currency) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                    <span class="text-sm font-medium text-gray-600">Total Received by PRIMA:</span>
                    <span class="text-sm font-semibold">{{ money($totalFee, $currency) }}</span>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">INITIAL PAYOUTS BY PRIMA
                </h3>
                @foreach ($groupedEarnings as $earning)
                    @if ($earning['type'] === 'concierge_bounty')
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">
                                @php
                                    /** @var User $user */
                                    $user = $earning['user'];
                                    $url = $user->concierge?->id
                                        ? ConciergeResource::getUrl('view', [$user->concierge->id])
                                        : null;
                                @endphp
                                @if ($url)
                                    <a href="{{ $url }}" class="text-indigo-600 hover:underline">
                                        Concierge Bounty ({{ BookingPercentages::NON_PRIME_CONCIERGE_PERCENTAGE }}% of
                                        {{ money($venueFee, $currency) }})
                                    </a>
                                @else
                                    Concierge Bounty ({{ BookingPercentages::NON_PRIME_CONCIERGE_PERCENTAGE }}% of
                                    {{ money($venueFee, $currency) }})
                                @endif
                            </span>
                            <span class="text-sm font-medium">{{ money($conciergeEarnings, $currency) }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mb-8">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">OPERATIONAL REMAINDER</h3>
                <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                    <span class="text-sm text-gray-600">Pool for Distribution:</span>
                    <span
                        class="text-sm font-semibold bg-blue-100 text-blue-800 px-2 py-0.5 rounded">{{ money($remainderNonPrime, $currency) }}</span>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">DISTRIBUTION OF REMAINDER
                </h3>
                @foreach ($groupedEarnings as $earning)
                    @if (in_array($earning['type'], ['partner_venue', 'partner_concierge', 'concierge_referral_1', 'concierge_referral_2']))
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">
                                @php
                                    /** @var User $user */
                                    $user = $earning['user'];
                                    $url = match ($earning['type']) {
                                        'partner_venue' => $user->partner?->id
                                            ? PartnerResource::getUrl('view', [$user->partner->id])
                                            : null,
                                        'partner_concierge' => $user->partner?->id
                                            ? PartnerResource::getUrl('view', [$user->partner->id])
                                            : null,
                                        'concierge_referral_1', 'concierge_referral_2' => $user->concierge?->id
                                            ? ConciergeResource::getUrl('view', [$user->concierge->id])
                                            : null,
                                        default => null,
                                    };
                                @endphp
                                @if ($url)
                                    <a href="{{ $url }}" class="text-indigo-600 hover:underline">
                                        {{ match ($earning['type']) {
                                            'concierge_referral_1' => 'Concierge Referral 1 (10%)',
                                            'concierge_referral_2' => 'Concierge Referral 2 (5%)',
                                            'partner_concierge' => 'Partner (Concierge Referrer)',
                                            'partner_venue' => 'Partner (Venue Referrer)',
                                            default => Str::title(str_replace('_', ' ', $earning['type'])),
                                        } }}
                                    </a>
                                @else
                                    {{ match ($earning['type']) {
                                        'concierge_referral_1' => 'Concierge Referral 1 (10%)',
                                        'concierge_referral_2' => 'Concierge Referral 2 (5%)',
                                        'partner_concierge' => 'Partner (Concierge Referrer)',
                                        'partner_venue' => 'Partner (Venue Referrer)',
                                        default => Str::title(str_replace('_', ' ', $earning['type'])),
                                    } }}
                                @endif
                            </span>
                            <span class="text-sm font-medium">{{ money($earning['amount'], $currency) }}</span>
                        </div>
                    @endif
                @endforeach
                <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                    <span class="text-sm font-medium text-gray-600">PRIMA Share from Remainder:</span>
                    <span class="text-sm font-semibold">{{ money($primaShare, $currency) }}</span>
                </div>
            </div>

            <div class="mt-8">
                <p class="text-xs font-semibold text-gray-500 text-center uppercase tracking-wider mb-4">FINAL NET
                    POSITION</p>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600">Venue Net:</span>
                        <span class="text-sm font-semibold text-red-600">-{{ money($totalFee, $currency) }}</span>
                    </div>
                    @foreach ($groupedEarnings as $earning)
                        @if (in_array($earning['type'], [
                                'concierge_bounty',
                                'partner_venue',
                                'partner_concierge',
                                'concierge_referral_1',
                                'concierge_referral_2',
                            ]))
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">
                                    @php
                                        /** @var User $user */
                                        $user = $earning['user'];
                                        $url = match ($earning['type']) {
                                            'concierge_bounty' => $user->concierge?->id
                                                ? ConciergeResource::getUrl('view', [$user->concierge->id])
                                                : null,
                                            'partner_venue', 'partner_concierge' => $user->partner?->id
                                                ? PartnerResource::getUrl('view', [$user->partner->id])
                                                : null,
                                            'concierge_referral_1', 'concierge_referral_2' => $user->concierge?->id
                                                ? ConciergeResource::getUrl('view', [$user->concierge->id])
                                                : null,
                                            default => null,
                                        };
                                    @endphp
                                    @if ($url)
                                        <a href="{{ $url }}" class="text-indigo-600 hover:underline">
                                            {{ match ($earning['type']) {
                                                'concierge_bounty' => $user->name . ' (Concierge) Net:',
                                                'concierge_referral_1' => $user->name . ' (Referral 1) Net:',
                                                'concierge_referral_2' => $user->name . ' (Referral 2) Net:',
                                                'partner_concierge' => $user->name . ' (Partner Concierge) Net:',
                                                'partner_venue' => $user->name . ' (Partner Venue) Net:',
                                                default => $user->name . ' Net:',
                                            } }}
                                        </a>
                                    @else
                                        {{ match ($earning['type']) {
                                            'concierge_bounty' => $user->name . ' (Concierge) Net:',
                                            'concierge_referral_1' => $user->name . ' (Referral 1) Net:',
                                            'concierge_referral_2' => $user->name . ' (Referral 2) Net:',
                                            'partner_concierge' => $user->name . ' (Partner Concierge) Net:',
                                            'partner_venue' => $user->name . ' (Partner Venue) Net:',
                                            default => $user->name . ' Net:',
                                        } }}
                                    @endif
                                </span>
                                <span
                                    class="text-sm font-semibold text-gray-800">+{{ money($earning['amount'], $currency) }}</span>
                            </div>
                        @endif
                    @endforeach
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600">PRIMA Net:</span>
                        <span class="text-sm font-semibold text-gray-800">+{{ money($primaShare, $currency) }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
