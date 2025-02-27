<x-mail::message>
# Hello from PRIMA!

You've been invited to PRIMA by {{ $referrer }} and your account is pending activation. Please click the link
below to secure your account and begin using the PRIMA system. Welcome aboard!

<div align="center" style="font-weight:bold">
    To activate your concierge account, please click below
</div>

<x-mail::button :url="$passwordResetUrl" color="brand">Secure Your Account</x-mail::button>

Thank you!

Team PRIMA
</x-mail::message>
