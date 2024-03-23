<x-mail::message>
# PRIMA Invoice #{{ $booking->id }}

Thank you for using PRIMA, please find your invoice attached.

<table>
<tr>
<td><strong>CUSTOMER:</strong></td>
<td>Andrew Weir</td>
</tr>
<tr>
<td><strong>AMOUNT PAID:</strong></td>
<td>$267.50</td>
</tr>
<tr>
<td><strong>DATE PAID:</strong></td>
<td>Mar 23, 2024</td>
</tr>
<tr>
<td><strong>PAYMENT METHOD:</strong></td>
<td>••••4242</td>
</tr>
<tr>
<td colspan="2" style="line-height:20px;">&nbsp;</td>
</tr>
<tr>
<td><strong>SUMMARY</strong></td>
<td></td>
</tr>
<tr>
<td>Mystic Pizza (3 guests)</td>
<td>$250.00</td>
</tr>
<tr>
<td>Tax (7%)</td>
<td>$17.50</td>
</tr>
<tr>
<td colspan="2" style="line-height:20px;">&nbsp;</td>
</tr>
<tr>
<td><strong>Amount Paid</strong></td>
<td>$267.50</td>
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
