<?php

namespace App\Constants;

class SmsTemplates
{
    public const array TEMPLATES = [
        'admin_venue_failed_to_confirm_booking' => 'PRIMA Reservation - Venue {venue_name} failed to confirm the reservation #{booking_id} scheduled for {booking_date}, at {booking_time}',
        'concierge_created' => "Welcome to PRIMA! Your account is now ready to earn commissions by booking reservations at the top restaurants.\n\nMore info about PRIMA: {more_info}\n\nHow our app works: {how_it_works}\n\nWelcome aboard!",
        'concierge_referral' => '{referrer} has invited you to join PRIMA, click {url} now to create your account. To learn about PRIMA click {how_it_works} - Welcome aboard!',
        'concierge_reminder' => 'Hi {first_name}, your PRIMA invite from {referrer} expires soon. Please click {url} to secure your account now. We look forward to working with you!',
        'concierge_special_request_accepted' => 'Hi from PRIMA! Your special request has been accepted from {venue}. Click {link} for more details.',
        'concierge_special_request_change_request' => 'Special request changes have been requested from {venue}. Click here for more details {link}.',
        'concierge_special_request_rejected' => 'Special request has been rejected from {venue}. Click here for more details {link}.',
        'customer_booking_confirmed_non_prime' => '👋 Hello from PRIMA VIP! Your reservation at {venue_name} on {booking_date} at {booking_time} has been booked by {concierge_name}. Please arrive within 15 minutes of your reservation and mention PRIMA VIP when checking in! To Modify Reservation Click {modify_url}. Thank you for booking with us. (https://primaapp.com)',
        'customer_booking_confirmed_prime' => '👋 Thank you for your trust! PRIMA is currently reaching out to management at {venue_name} to secure your table. If anything goes wrong, we will contact you, however, 99.9% of all tables are confirmed in under 15 minutes. (https://primaapp.com)',
        'customer_booking_confirmed_prime_omakase' => '👋 Thank you for your trust! PRIMA is currently reaching out to management at {venue_name} to secure your omakase experience. If anything goes wrong, we will contact you; however, 99.9% of all tables are confirmed in under 15 minutes. The fee you paid will be applied towards your omakase experience\'s final bill. View your invoice at {invoice_url}. Questions? Reply here. (https://primaapp.com)',
        'customer_booking_payment_form' => 'Hello from PRIMA VIP! We\'ve received your request for {venue_name} on {booking_date} at {booking_time} for {guest_count} guests. The fee is {amount}. Secure your spot now: {payment_url}. Not applied towards table spend.',
        'customer_modification_requested' => 'We have received your reservation change request from {concierge_name}. We are reaching out to the restaurant to confirm and will notify you shortly. Thank you! Team PRIMA',
        'customer_booking_received_non_prime_big_group' => '👋 Hello from PRIMA VIP! We’ve received your reservation request at {venue_name} on {booking_date} at {booking_time}. This large-party request must be approved by {venue_name}. Most reservations are confirmed within 30 minutes. We will notify you as soon as it’s secured. Thank you for choosing PRIMA VIP! (https://primaapp.com)',
        'customer_booking_confirmed_non_prime_big_group' => '🎉 Great news! Your reservation at {venue_name} on {booking_date} at {booking_time} is confirmed. See you then! (https://primaapp.com)',
        'partner_created' => 'Welcome to PRIMA! Your account has been created. Please click {login_url} to login and update your payment info and begin making reservations. Thank you for joining us!',
        'two_factor_code' => 'Do not share this code with anyone. Your 2FA login code for PRIMA is {code}',
        'venue_contact_booking_confirmed' => 'PRIMA Booking @ {venue_name} {booking_date} @ {booking_time}, {guest_count} guests, {guest_name}, {guest_phone}. Click {confirmation_url} to confirm.',
        'venue_contact_booking_confirmed_notes' => "PRIMA Booking @ {venue_name} {booking_date} @ {booking_time}, {guest_count} guests, {guest_name}, {guest_phone}. Click {confirmation_url} to confirm.\n\nNotes: {notes}",
        'venue_contact_booking_confirmed_reminder' => "Reminder:\n\nPRIMA Booking @ {venue_name} {booking_date} @ {booking_time}, {guest_count} guests, {guest_name}, {guest_phone}. Click {confirmation_url} to confirm.",
        'venue_contact_booking_confirmed_reminder_notes' => "Reminder:\n\nPRIMA Booking @ {venue_name} {booking_date} @ {booking_time}, {guest_count} guests, {guest_name}, {guest_phone}. Click {confirmation_url} to confirm.\n\nNotes: {notes}",
        'venue_contact_login' => 'Click the link to login: {login_url}',
        'venue_special_request_confirmation' => 'PRIMA Special Request from {customer_name}: {booking_date} at {booking_time}, {party_size} guests, Min. Spend {minimum_spend}. Click {confirmation_url} to view this request.',
        'venue_welcome' => 'Thank you for joining PRIMA! We look forward to working with you! If you have any questions, our team is always available.',
        'customer_booking_refunded' => 'Hi {guest_name}, we have refunded your reservation fee of {amount} for {venue_name}. Thank you! PRIMA VIP.',
        'venue_modification_request' => 'PRIMA Change Request @ {venue_name}: {guest_name} {guest_phone} {booking_date} {booking_time} Change requested: {changes_requested}. Click {confirmation_url} to respond.',
        'customer_modification_approved' => '👋 Thank you for your patience! Your requested changes have been approved by {venue_name} and your reservation has been updated. Thank you for using PRIMA VIP (https://primaapp.com)',
        'customer_modification_rejected' => 'Sorry, your changes have not been approved by {venue_name}. Please contact your PRIMA Concierge to create a new reservation.',
        'concierge_modification_approved' => 'Your requested reservation change at {venue_name} is approved. We have notified the customer.',
        'concierge_modification_rejected' => 'We are sorry, but {venue_name} cannot accommodate your change request. If you\'d like to have this reservation cancelled, you may do so through the PRIMA App.',
        'concierge_first_booking' => "Thank you for using PRIMA for the first time! Your reservation for {guest_name} is currently being transmitted to {venue_name}. Reservations may take up to 30 minutes to show up at the restaurant, however, rest assured that all is okay. Thank you!\n\n(This notification is only sent for the first booking)",
        'venue_booking_cancelled' => 'PRIMA Notice: {guest_name} @ {guest_phone} has cancelled the reservation at {venue_name} on {booking_date}. Please update your records. Thank you.',
        'customer_booking_reminder' => 'Your reservation at {venue_name} is coming up in 30 minutes! When checking in, please mention PRIMA VIP to the host for prompt service. Your booking details: {link} Thank you!',
        'customer_booking_follow_up' => 'Hello from PRIMA VIP! We hope you enjoyed {venue_name} yesterday! You are now a VIP and with PRIMA, you get instant access to top restaurants throughout Miami, Los Angeles and Ibiza! Click >> {link} to instantly book at any of our participating restaurants! Learn More about PRIMA here: https://primaapp.com',
    ];
}
