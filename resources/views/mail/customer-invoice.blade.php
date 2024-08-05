<x-mail::message>
    # PRIMA Invoice #{{ $booking->id }}

    Thank you for using PRIMA, please find your invoice attached.

    <table>
        <tr>
            <td><strong>CUSTOMER:</strong></td>
            <td>
                {{ $booking->guest_name }}
            </td>
        </tr>
        <tr>
            <td><strong>AMOUNT PAID:</strong></td>
            <td>
                {{ money($booking->total_with_tax_in_cents, $booking->currency) }}
            </td>
        </tr>
        <tr>
            <td><strong>DATE PAID:</strong></td>
            <td>
                {{ $booking->confirmed_at->format('D, M j g:i a') }}
            </td>
        </tr>
        <tr>
            <td><strong>PAYMENT METHOD:</strong></td>
            <td>••••{{ $booking->stripe_charge->paymentMethodDetails->card->last4 }}</td>
        </tr>
        <tr>
            <td colspan="2" style="line-height:20px;">&nbsp;</td>
        </tr>
        <tr>
            <td><strong>SUMMARY</strong></td>
            <td></td>
        </tr>
        <tr>
            <td>
                {{ $booking->venue->name }} ({{ $booking->guest_count }} guests)
            </td>
            <td>
                {{ money($booking->total_fee) }}
            </td>
        </tr>
        @if ($booking->tax > 0)
            <tr>
                <td>{{ $region->tax_rate_term }} ({{ $booking->tax * 100 }}%)</td>
                <td>
                    {{ money($booking->tax_amount_in_cents, $booking->currency) }}
                </td>
            </tr>
        @endif
        <tr>
            <td colspan="2" style="line-height:20px;">&nbsp;</td>
        </tr>
        <tr>
            <td><strong>Amount Paid</strong></td>
            <td>
                {{ money($booking->total_with_tax_in_cents, $booking->currency) }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="line-height:20px;">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2" style="line-height:20px;">&nbsp;</td>
        </tr>
    </table>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
