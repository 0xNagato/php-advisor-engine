<x-mail::message>
    # Welcome to PRIMA!

    We are excited to have you as a member of our team and are looking forward to working with you. In order to sign
    into the PRIMA system, please click the link below to create your password.

    Once you've logged in, ensure that you update your payment info so we know where to send your payments.

    If you have any questions, please do not hesitate to contact us, and we will do our best to get back to you as soon
    as possible.

    <x-mail::button :url="$passwordResetUrl" color="brand">Secure Your Account</x-mail::button>

    Sincerely,

    Team PRIMA
</x-mail::message>
