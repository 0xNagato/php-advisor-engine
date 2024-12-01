<?php

namespace App\Constants;

class SmsTemplates
{
    public const TEMPLATES = [
        'admin_venue_failed_to_confirm_booking' => 'PRIMA Reservation - Venue {venue_name} failed to confirm the reservation #{booking_id} scheduled for {booking_date}, at {booking_time}',
        'concierge_created' => 'Welcome to PRIMA! Click https://bit.ly/PRIMAVIP to learn about how PRIMA works! We look forward to working with you!',
        'concierge_referral' => '{referrer} has invited you to join PRIMA, click {url} now to create your account. To learn about PRIMA click https://bit.ly/PRIMA4 - Welcome aboard!',
        'concierge_reminder' => 'Hi {first_name}, your PRIMA invite from {referrer} expires soon. Please click {url} to secure your account now. We look forward to working with you!',
        'concierge_special_request_accepted' => 'Hi from PRIMA! Your special request has been accepted from {venue}. Click {link} for more details.',
        'concierge_special_request_change_request' => 'Special request changes have been requested from {venue}. Click here for more details {link}.',
        'concierge_special_request_rejected' => 'Special request has been rejected from {venue}. Click here for more details {link}.',
        'customer_booking_confirmed_non_prime' => 'Hello from PRIMA VIP! Your reservation at {venue_name} {booking_date} at {booking_time} has been booked by {concierge_name} and is now confirmed. Please arrive within 15 minutes of your reservation or your table may be released. Thank you for booking with us!',
        'customer_booking_confirmed_prime' => 'PRIMA reservation at {venue_name} {booking_date} at {booking_time} with {guest_count} guests. View your invoice at {invoice_url}.',
        'customer_booking_payment_form' => 'Your reservation at {venue_name} is pending. Please click {payment_url} to secure your booking within the next 5 minutes.',
        'partner_created' => 'Welcome to PRIMA! Your account has been created. Please click {login_url} to login and update your payment info and begin making reservations. Thank you for joining us!',
        'two_factor_code' => 'Do not share this code with anyone. Your 2FA login code for PRIMA is {code}',
        'venue_contact_booking_confirmed' => 'Pending Booking @{venue_name}: {booking_date} @ {booking_time}, {guest_name}, {guest_count} guests, {guest_phone}. Click {confirmation_url} to confirm.',
        'venue_contact_login' => 'Click the link to login: {login_url}',
        'venue_special_request_confirmation' => 'PRIMA Special Request from {customer_name}: {booking_date} at {booking_time}, {party_size} guests, Min. Spend {minimum_spend}. Click {confirmation_url} to view this request.',
        'venue_welcome' => '❤️ Thank you for joining PRIMA! Our concierge team is currently being onboarded and will start generating reservations soon! We will notify you via text as soon as we are ready to launch! With gratitude, Team PRIMA.',
        'customer_booking_refunded' => 'Hi {guest_name}, we have refunded your reservation fee of {amount} for {venue_name}. Thank you! PRIMA VIP.',
    ];
}
