<?php

use App\Constants\SmsTemplates;
use App\Data\SmsData;
use App\Models\Booking;
use App\Models\Venue;
use App\Notifications\Booking\VenueContactBookingAutoApproved;
use Illuminate\Notifications\Messages\MailMessage;

beforeEach(function () {
    $this->venue = Venue::factory()->create([
        'name' => 'Test Restaurant',
        'timezone' => 'America/New_York',
    ]);

    $this->scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
    ]);

    $this->booking = Booking::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 4,
        'guest_first_name' => 'John',
        'guest_last_name' => 'Doe',
        'guest_phone' => '+1234567890',
        'guest_email' => 'john@example.com',
        'booking_at' => '2024-03-15 19:30:00',
        'notes' => null,
        'venue_confirmed_at' => now(),
    ]);

    $this->contact = $this->venue->contacts->first();

    $this->notification = new VenueContactBookingAutoApproved($this->booking);
});

it('generates correct SMS message without notes', function () {
    $smsMessage = $this->notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateKey)->toBe('venue_contact_booking_auto_approved');
    expect($smsMessage->templateData)->toEqual([
        'platform_name' => 'your booking platform',
        'venue_name' => 'Test Restaurant',
        'booking_date' => 'Mar 15th',
        'booking_time' => '7:30 PM',
        'guest_count' => 4,
        'guest_name' => 'John Doe',
        'guest_phone' => '+1234567890',
    ]);
});

it('generates correct SMS message with notes', function () {
    $this->booking->update(['notes' => 'Birthday celebration']);

    $smsMessage = $this->notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateKey)->toBe('venue_contact_booking_auto_approved_notes');
    expect($smsMessage->templateData)->toEqual([
        'platform_name' => 'your booking platform',
        'venue_name' => 'Test Restaurant',
        'booking_date' => 'Mar 15th',
        'booking_time' => '7:30 PM',
        'guest_count' => 4,
        'guest_name' => 'John Doe',
        'guest_phone' => '+1234567890',
        'notes' => 'Birthday celebration',
    ]);
});

it('uses correct SMS template for bookings without notes', function () {
    $template = SmsTemplates::TEMPLATES['venue_contact_booking_auto_approved'];

    expect($template)->toBe(
        'The following reservation has been added to {platform_name}: PRIMA Booking @ {venue_name} {booking_date} @ {booking_time}, {guest_count} guests, {guest_name}, {guest_phone}.'
    );
});

it('uses correct SMS template for bookings with notes', function () {
    $template = SmsTemplates::TEMPLATES['venue_contact_booking_auto_approved_notes'];

    expect($template)->toBe(
        "The following reservation has been added to {platform_name}: PRIMA Booking @ {venue_name} {booking_date} @ {booking_time}, {guest_count} guests, {guest_name}, {guest_phone}.\n\nNotes: {notes}"
    );
});

it('generates correct email for venue contact', function () {
    $mailMessage = $this->notification->toMail($this->contact);

    expect($mailMessage)->toBeInstanceOf(MailMessage::class);
    expect($mailMessage->subject)->toBe('PRIMA Auto-Approved Booking - Test Restaurant');
    expect($mailMessage->greeting)->toBe('Hello from PRIMA!');
});

it('generates correct email for admin notification', function () {
    $mailMessage = $this->notification->toMail('admin@example.com');

    expect($mailMessage->subject)->toBe('PRIMA Auto-Approved Booking - Admin Notification');
});

it('includes special notes in email when present', function () {
    $this->booking->update(['notes' => 'Vegetarian preferences']);

    $mailMessage = $this->notification->toMail($this->contact);

    expect($mailMessage)->toBeInstanceOf(MailMessage::class);
});

it('formats booking time correctly in different timezone', function () {
    $venue = Venue::factory()->create([
        'name' => 'LA Restaurant',
        'timezone' => 'America/Los_Angeles',
    ]);

    $scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $venue->id,
    ]);

    $booking = Booking::factory()->create([
        'schedule_template_id' => $scheduleTemplate->id,
        'booking_at' => '2024-03-15 19:30:00', // 7:30 PM
        'guest_first_name' => 'Jane',
        'guest_last_name' => 'Smith',
        'guest_phone' => '+1555123456',
        'guest_count' => 2,
    ]);

    $notification = new VenueContactBookingAutoApproved($booking);
    $smsMessage = $notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateData['booking_time'])->toBe('7:30 PM');
    expect($smsMessage->templateData['booking_date'])->toBe('Mar 15th');
});

it('returns correct notification channels for venue contact', function () {
    // Create a mock VenueContactData instead of using the real one
    $mockContact = Mockery::mock(\App\Data\VenueContactData::class);
    $mockContact->shouldReceive('toChannel')->andReturn(['sms', 'mail']);

    $channels = $this->notification->via($mockContact);

    expect($channels)->toBe(['sms', 'mail']);
});

it('returns mail channel for admin notifications', function () {
    $channels = $this->notification->via('admin@example.com');

    expect($channels)->toBe(['mail']);
});

it('generates correct array representation', function () {
    $array = $this->notification->toArray($this->contact);

    expect($array)->toEqual([
        'booking_id' => $this->booking->id,
        'venue_id' => $this->venue->id,
        'venue_name' => 'Test Restaurant',
        'guest_name' => 'John Doe',
        'guest_count' => 4,
        'booking_at' => $this->booking->booking_at,
        'auto_approved' => true,
    ]);
});

it('handles edge case with very long venue name', function () {
    $longNameVenue = Venue::factory()->create([
        'name' => 'This Is A Really Long Restaurant Name That Might Cause Issues',
        'timezone' => 'America/New_York',
    ]);

    $scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $longNameVenue->id,
    ]);

    $booking = Booking::factory()->create([
        'schedule_template_id' => $scheduleTemplate->id,
        'guest_count' => 2,
        'guest_first_name' => 'Test',
        'guest_last_name' => 'User',
        'guest_phone' => '+1234567890',
        'booking_at' => '2024-03-15 19:30:00',
    ]);

    $notification = new VenueContactBookingAutoApproved($booking);
    $smsMessage = $notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateData['venue_name'])->toBe('This Is A Really Long Restaurant Name That Might Cause Issues');
});

it('handles midnight booking time correctly', function () {
    $this->booking->update(['booking_at' => '2024-03-15 00:00:00']);

    $smsMessage = $this->notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateData['booking_time'])->toBe('12:00 AM');
});

it('handles noon booking time correctly', function () {
    $this->booking->update(['booking_at' => '2024-03-15 12:00:00']);

    $smsMessage = $this->notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateData['booking_time'])->toBe('12:00 PM');
});

it('generates correct platform name for CoverManager', function () {
    // Add CoverManager platform to venue
    $this->venue->platforms()->create([
        'platform_type' => 'covermanager',
        'is_enabled' => true,
        'configuration' => [],
    ]);

    $smsMessage = $this->notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateData['platform_name'])->toBe('CoverManager');
});

it('generates correct platform name for Restoo', function () {
    // Add Restoo platform to venue
    $this->venue->platforms()->create([
        'platform_type' => 'restoo',
        'is_enabled' => true,
        'configuration' => [],
    ]);

    $smsMessage = $this->notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateData['platform_name'])->toBe('Restoo');
});

it('falls back to generic platform name when no enabled platforms found', function () {
    // No platforms added to venue, should use fallback
    $smsMessage = $this->notification->toSms($this->contact);

    expect($smsMessage)->toBeInstanceOf(SmsData::class);
    expect($smsMessage->templateData['platform_name'])->toBe('your booking platform');
});
