<x-mail::message>
    # Hello from PRIMA!

    You've been invited to PRIMA by {{ $referrer }} and your account is pending activation. Please click the link
    below to secure your account and begin using the PRIMA system. Welcome aboard!

    Note: Venues are currently being onboarded, we will provide you access to our demo site so that you can see how
    PRIMA works.

    <div align="center" style="font-weight:bold">
        To activate your concierge account, please click below
    </div>

    <x-mail::button :url="$passwordResetUrl" color="brand">Secure Your Account</x-mail::button>

    Thank you!

    Team PRIMA
</x-mail::message>
