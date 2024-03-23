<x-mail::message>
# PRIMA Invoice #{{ $booking->id }}

Thank you for using PRIMA, please find your invoice attached.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
