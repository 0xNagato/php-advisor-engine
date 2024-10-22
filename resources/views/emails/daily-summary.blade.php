@component('mail::message')
# PRIMA Daily Summary for {{ $summary['date'] }}

Here's a summary of yesterday's activities:

- New Bookings: {{ $summary['new_bookings'] }}
- New Venues: {{ $summary['new_venues'] }}
- New Concierges Invited: {{ $summary['new_concierges_invited'] }}
- New Concierges Secured: {{ $summary['new_concierges_secured'] }}
- Total Amount: ${{ number_format($summary['total_amount'], 2) }} USD
- Platform Earnings: ${{ number_format($summary['platform_earnings'], 2) }} USD

## Currency Breakdown:
@foreach ($summary['currency_breakdown'] as $currency => $amount)
- {{ $currency }}: {{ number_format($amount / 100, 2) }}
@endforeach

## Top Referrers (Invitations):
@foreach ($summary['top_referrers_invitations'] as $referrer)
- {{ $referrer['name'] }}: {{ $referrer['count'] }} invitations
@endforeach

## Top Referrers (Secured Accounts):
@foreach ($summary['top_referrers_secured'] as $referrer)
- {{ $referrer['name'] }}: {{ $referrer['count'] }} secured accounts
@endforeach

## New Concierges:
@foreach ($summary['new_concierges_list'] as $concierge)
- [{{ $concierge['name'] }}]({{ $concierge['profile_url'] }})
@endforeach

Thank you for your attention to this daily summary.
@endcomponent
