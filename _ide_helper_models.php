<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $sender_id
 * @property string $title
 * @property string $message
 * @property string|null $region
 * @property array|null $recipient_roles
 * @property array|null $recipient_user_ids
 * @property string|null $call_to_action_title
 * @property string|null $call_to_action_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $published_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User $sender
 * @method static \Database\Factories\AnnouncementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement query()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereCallToActionTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereCallToActionUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereRecipientRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereRecipientUserIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAnnouncement {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $schedule_template_id
 * @property string $uuid
 * @property int $concierge_id
 * @property int|null $partner_concierge_id
 * @property int|null $partner_venue_id
 * @property string|null $guest_first_name
 * @property string|null $guest_last_name
 * @property string|null $guest_email
 * @property string|null $guest_phone
 * @property \Illuminate\Support\Carbon $booking_at
 * @property int $guest_count
 * @property int $total_fee
 * @property string $currency
 * @property \App\Enums\BookingStatus $status
 * @property int $is_prime
 * @property int $no_show
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $stripe_charge_id
 * @property string|null $stripe_payment_intent_id
 * @property \Spatie\LaravelData\Contracts\BaseData|null $stripe_charge
 * @property int $venue_earnings
 * @property int $concierge_earnings
 * @property int $charity_earnings
 * @property int $platform_earnings
 * @property int $partner_concierge_fee
 * @property int $partner_venue_fee
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property string|null $concierge_referral_type
 * @property \Illuminate\Support\Carbon|null $venue_confirmed_at
 * @property \Illuminate\Support\Carbon|null $resent_venue_confirmation_at
 * @property int|null $tax_amount_in_cents
 * @property float|null $tax
 * @property int|null $total_with_tax_in_cents
 * @property string|null $city
 * @property string|null $invoice_path
 * @property string|null $notes
 * @property int|null $vip_code_id
 * @property-read \App\Models\Concierge $concierge
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Earning> $earnings
 * @property-read int|null $earnings_count
 * @property-read mixed $guest_name
 * @property-read mixed $local_formatted_guest_phone
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Partner|null $partnerConcierge
 * @property-read \App\Models\Partner|null $partnerVenue
 * @property-read mixed $prime_time
 * @property-read \App\Models\ScheduleWithBooking|null $schedule
 * @property-read \App\Models\Venue|null $venue
 * @property-read \App\Models\VipCode|null $vipCode
 * @method static \Illuminate\Database\Eloquent\Builder|Booking confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking confirmedOrNoShow()
 * @method static \Database\Factories\BookingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Booking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking noShow()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking query()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereBookingAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereCharityEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereClickedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereConciergeEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereConciergeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereConciergeReferralType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereGuestCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereGuestEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereGuestFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereGuestLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereGuestPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereInvoicePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereIsPrime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereNoShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePartnerConciergeFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePartnerConciergeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePartnerVenueFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePartnerVenueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePlatformEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereResentVenueConfirmationAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereScheduleTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereStripeCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereStripeChargeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereStripePaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTaxAmountInCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTotalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTotalWithTaxInCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereVenueConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereVenueEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereVipCodeId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBooking {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $hotel_name
 * @property string|null $hotel_phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Concierge> $concierges
 * @property-read int|null $concierges_count
 * @property-read mixed $payout_percentage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Referral> $referrals
 * @property-read int|null $referrals_count
 * @property-read Concierge|null $referringConcierge
 * @property-read mixed $sales
 * @property-read mixed $sales_this_month
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VipCode> $vipCodes
 * @property-read int|null $vip_codes_count
 * @method static \Database\Factories\ConciergeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge query()
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge whereHotelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge whereHotelPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Concierge whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperConcierge {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property int $verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\DeviceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Device newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Device newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Device query()
 * @method static \Illuminate\Database\Eloquent\Builder|Device whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Device whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Device whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Device whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Device whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Device whereVerified($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDevice {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $booking_id
 * @property int|null $payment_id
 * @property string $type
 * @property int $amount
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $confirmed_at
 * @property int $percentage
 * @property string $percentage_of
 * @property-read \App\Models\Booking $booking
 * @property-read \App\Models\Payment|null $payment
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Earning confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder|Earning newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Earning newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Earning query()
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning wherePercentageOf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperEarning {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $message
 * @property string|null $exception_message
 * @property string|null $exception_trace
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback query()
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback whereExceptionMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback whereExceptionTrace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Feedback whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperFeedback {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $announcement_id
 * @property string|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Announcement $announcement
 * @property-read \App\Models\User|null $recipient
 * @method static \Database\Factories\MessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereAnnouncementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMessage {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $percentage
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $conciergeBookings
 * @property-read int|null $concierge_bookings_count
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $venueBookings
 * @property-read int|null $venue_bookings_count
 * @method static \Database\Factories\PartnerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Partner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Partner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Partner query()
 * @method static \Illuminate\Database\Eloquent\Builder|Partner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Partner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Partner wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Partner whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Partner whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Partner withAllBookings()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPartner {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $title
 * @property int $amount
 * @property string $currency
 * @property \App\Enums\PaymentStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Earning> $earnings
 * @property-read int|null $earnings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PaymentItem> $items
 * @property-read int|null $items_count
 * @method static \Database\Factories\PaymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayment {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $payment_id
 * @property string $currency
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Payment $payment
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentItem whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPaymentItem {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property int $referrer_id
 * @property string|null $email
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $user_id
 * @property string|null $secured_at
 * @property string $type
 * @property string $referrer_type
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $notified_at
 * @property-read mixed $has_secured
 * @property-read mixed $label
 * @property-read mixed $local_formatted_phone
 * @property-read mixed $name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\User $referrer
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Referral newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral query()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereReferrerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereReferrerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereSecuredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReferral {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property float $lat
 * @property float $lon
 * @property string $currency
 * @property string $currency_symbol
 * @property float $tax_rate
 * @property string $tax_rate_term
 * @property string $country
 * @property string $timezone
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Venue> $venues
 * @property-read int|null $venues_count
 * @method static \Illuminate\Database\Eloquent\Builder|Region active()
 * @method static \Illuminate\Database\Eloquent\Builder|Region newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Region newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Region query()
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereCurrencySymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereTaxRateTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Region whereTimezone($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperRegion {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $venue_id
 * @property string $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property bool $is_available
 * @property int $available_tables
 * @property bool $prime_time
 * @property int $prime_time_fee
 * @property int $party_size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Venue $venue
 * @method static \Database\Factories\ScheduleTemplateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereAvailableTables($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate wherePartySize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate wherePrimeTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate wherePrimeTimeFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleTemplate whereVenueId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperScheduleTemplate {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $schedule_id
 * @property int $schedule_template_id
 * @property int $venue_id
 * @property string $schedule_start
 * @property string $schedule_end
 * @property int $is_available
 * @property bool $is_bookable
 * @property int $remaining_tables
 * @property int $effective_fee
 * @property bool $prime_time
 * @property int $party_size
 * @property string $booking_date
 * @property string $booking_at
 * @property string $start_time
 * @property string $end_time
 * @property int $id
 * @property string $day_of_week
 * @property int $available_tables
 * @property int $prime_time_fee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read mixed $formatted_end_time
 * @property-read mixed $formatted_start_time
 * @property-read mixed $has_low_inventory
 * @property-read \App\Models\Venue|null $venue
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking query()
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereAvailableTables($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereBookingAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereBookingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereEffectiveFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking wherePartySize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking wherePrimeTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking wherePrimeTimeFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereRemainingTables($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereScheduleEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereScheduleStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereScheduleTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ScheduleWithBooking whereVenueId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperScheduleWithBooking {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $phone_number
 * @property string $message
 * @property string $response
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Booking|null $booking
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SmsResponse whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSmsResponse {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $venue_id
 * @property string $date
 * @property int $fee
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Venue $venue
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue query()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingVenue whereVenueId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSpecialPricingVenue {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $uuid
 * @property int $id
 * @property int $venue_id
 * @property int $concierge_id
 * @property \Illuminate\Support\Carbon $booking_date
 * @property \Illuminate\Support\Carbon $booking_time
 * @property int $party_size
 * @property string|null $special_request
 * @property string $customer_first_name
 * @property string $customer_last_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property int $commission_requested_percentage
 * @property int $minimum_spend
 * @property \App\Enums\SpecialRequestStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $schedule_template_id
 * @property int|null $booking_id
 * @property string|null $restaurant_message
 * @property \Spatie\LaravelData\DataCollection|null $conversations
 * @property \ArrayObject|null $meta
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\Concierge $concierge
 * @property-read mixed $customer_name
 * @property-read \App\Models\ScheduleTemplate|null $scheduleTemplate
 * @property-read \App\Models\Venue $venue
 * @property-read mixed $venue_total_fee
 * @method static \Database\Factories\SpecialRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereBookingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereBookingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereCommissionRequestedPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereConciergeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereConversations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereCustomerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereCustomerFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereCustomerLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereCustomerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereMinimumSpend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest wherePartySize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereRestaurantMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereScheduleTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereSpecialRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialRequest whereVenueId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSpecialRequest {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property string|null $profile_photo_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $secured_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $trial_ends_at
 * @property string|null $phone
 * @property \ArrayObject|null $payout
 * @property int $charity_percentage
 * @property int|null $partner_referral_id
 * @property int|null $concierge_referral_id
 * @property string $timezone
 * @property string|null $address_1
 * @property string|null $address_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property string|null $region
 * @property \Spatie\LaravelData\Contracts\BaseData|null $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog> $authentications
 * @property-read int|null $authentications_count
 * @property-read mixed $avatar
 * @property-read \App\Models\Concierge|null $concierge
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Device> $devices
 * @property-read int|null $devices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Earning> $earnings
 * @property-read int|null $earnings_count
 * @property-read mixed $has_secured
 * @property-read mixed $international_formatted_phone_number
 * @property-read mixed $label
 * @property-read \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog|null $latestAuthentication
 * @property-read mixed $local_formatted_phone
 * @property-read mixed $main_role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read mixed $name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Partner|null $partner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \App\Models\Referral|null $referral
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Referral> $referrals
 * @property-read int|null $referrals_count
 * @property-read User|null $referrer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Announcement> $sentAnnouncements
 * @property-read int|null $sent_announcements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read mixed $unread_message_count
 * @property-read \App\Models\UserCode|null $userCode
 * @property-read \App\Models\Venue|null $venue
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAddress1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAddress2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCharityPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereConciergeReferralId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCurrentTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePartnerReferralId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePayout($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePmLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePmType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSecuredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereZip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\UserCodeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|UserCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCode whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserCode {}
}

namespace App\Models{
/**
 * Class Venue
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string $contact_phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $payout_venue
 * @property string|null $primary_contact_name
 * @property int $booking_fee
 * @property int $increment_fee
 * @property int $non_prime_fee_per_head
 * @property string $non_prime_type
 * @property array|null $open_days
 * @property \Spatie\LaravelData\DataCollection|null $contacts
 * @property int $is_suspended
 * @property array|null $non_prime_time
 * @property array|null $business_hours
 * @property array|null $party_sizes
 * @property int|null $minimum_spend
 * @property string $region
 * @property string|null $logo_path
 * @property \App\Enums\VenueStatus $status
 * @property-read \App\Models\Region|null $inRegion
 * @property-read mixed $logo
 * @property-read \App\Models\Partner|null $partnerReferral
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ScheduleTemplate> $scheduleTemplates
 * @property-read int|null $schedule_templates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ScheduleWithBooking> $schedules
 * @property-read int|null $schedules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SpecialPricingVenue> $specialPricing
 * @property-read int|null $special_pricing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VenueTimeSlot> $timeSlots
 * @property-read int|null $time_slots_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Venue available()
 * @method static \Database\Factories\VenueFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Venue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Venue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Venue query()
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereBookingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereBusinessHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereContacts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereIncrementFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereIsSuspended($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereMinimumSpend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereNonPrimeFeePerHead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereNonPrimeTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereNonPrimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereOpenDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue wherePartySizes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue wherePayoutVenue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue wherePrimaryContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Venue whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVenue {}
}

namespace App\Models{
/**
 * Class VenueTimeSlot
 * 
 * Represents a specific time slot for a venue. This is an override for a ScheduleTemplate.
 * If a VenueTimeSlot is found, it will be used by the ScheduleWithBookings view instead of the default ScheduleTemplate.
 *
 * @property int $id
 * @property int $schedule_template_id
 * @property string $booking_date
 * @property int $prime_time
 * @property int|null $prime_time_fee
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ScheduleTemplate $scheduleTemplate
 * @property-read \App\Models\Venue|null $venue
 * @method static \Database\Factories\VenueTimeSlotFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot whereBookingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot wherePrimeTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot wherePrimeTimeFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot whereScheduleTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VenueTimeSlot whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVenueTimeSlot {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $code
 * @property int $concierge_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \App\Models\Concierge $concierge
 * @property-read mixed $confirmed_bookings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Earning> $earnings
 * @property-read int|null $earnings_count
 * @property-read mixed $link
 * @property-read mixed $total_earnings_grouped_by_currency
 * @property-read mixed $total_earnings_in_u_s_d
 * @method static \Database\Factories\VipCodeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode whereConciergeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VipCode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperVipCode {}
}

