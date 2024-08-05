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

namespace App\Models{use AllowDynamicProperties;use Database\Factories\AnnouncementFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $sender_id
     * @property string $title
     * @property string $message
     * @property string|null $region
     * @property array|null $recipient_roles
     * @property array|null $recipient_user_ids
     * @property string|null $call_to_action_title
     * @property string|null $call_to_action_url
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property string|null $published_at
     * @property-read Collection<int, Message> $messages
     * @property-read int|null $messages_count
     * @property-read User $sender
     *
     * @method static AnnouncementFactory factory($count = null, $state = [])
     * @method static Builder|Announcement newModelQuery()
     * @method static Builder|Announcement newQuery()
     * @method static Builder|Announcement query()
     * @method static Builder|Announcement whereCallToActionTitle($value)
     * @method static Builder|Announcement whereCallToActionUrl($value)
     * @method static Builder|Announcement whereCreatedAt($value)
     * @method static Builder|Announcement whereId($value)
     * @method static Builder|Announcement whereMessage($value)
     * @method static Builder|Announcement wherePublishedAt($value)
     * @method static Builder|Announcement whereRecipientRoles($value)
     * @method static Builder|Announcement whereRecipientUserIds($value)
     * @method static Builder|Announcement whereRegion($value)
     * @method static Builder|Announcement whereSenderId($value)
     * @method static Builder|Announcement whereTitle($value)
     * @method static Builder|Announcement whereUpdatedAt($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperAnnouncement {}
}

namespace App\Models{use AllowDynamicProperties;use App\Enums\BookingStatus;use Database\Factories\BookingFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Notifications\DatabaseNotification;use Illuminate\Notifications\DatabaseNotificationCollection;use Illuminate\Support\Carbon;use Spatie\LaravelData\Contracts\BaseData;

    /**
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
     * @property Carbon $booking_at
     * @property int $guest_count
     * @property int $total_fee
     * @property string $currency
     * @property BookingStatus $status
     * @property int $is_prime
     * @property int $no_show
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property string|null $stripe_charge_id
     * @property BaseData|null $stripe_charge
     * @property int $venue_earnings
     * @property int $concierge_earnings
     * @property int $charity_earnings
     * @property int $platform_earnings
     * @property int $partner_concierge_fee
     * @property int $partner_venue_fee
     * @property Carbon|null $clicked_at
     * @property Carbon|null $confirmed_at
     * @property string|null $concierge_referral_type
     * @property Carbon|null $venue_confirmed_at
     * @property Carbon|null $resent_venue_confirmation_at
     * @property int|null $tax_amount_in_cents
     * @property float|null $tax
     * @property int|null $total_with_tax_in_cents
     * @property string|null $city
     * @property string|null $invoice_path
     * @property string|null $notes
     * @property-read Concierge $concierge
     * @property-read Collection<int, Earning> $earnings
     * @property-read int|null $earnings_count
     * @property-read mixed $guest_name
     * @property-read mixed $local_formatted_guest_phone
     * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
     * @property-read int|null $notifications_count
     * @property-read Partner|null $partnerConcierge
     * @property-read Partner|null $partnerVenue
     * @property-read mixed $prime_time
     * @property-read Venue|null $venue
     * @property-read ScheduleWithBooking|null $schedule
     *
     * @method static Builder|Booking confirmed()
     * @method static Builder|Booking confirmedOrNoShow()
     * @method static BookingFactory factory($count = null, $state = [])
     * @method static Builder|Booking newModelQuery()
     * @method static Builder|Booking newQuery()
     * @method static Builder|Booking noShow()
     * @method static Builder|Booking query()
     * @method static Builder|Booking whereBookingAt($value)
     * @method static Builder|Booking whereCharityEarnings($value)
     * @method static Builder|Booking whereCity($value)
     * @method static Builder|Booking whereClickedAt($value)
     * @method static Builder|Booking whereConciergeEarnings($value)
     * @method static Builder|Booking whereConciergeId($value)
     * @method static Builder|Booking whereConciergeReferralType($value)
     * @method static Builder|Booking whereConfirmedAt($value)
     * @method static Builder|Booking whereCreatedAt($value)
     * @method static Builder|Booking whereCurrency($value)
     * @method static Builder|Booking whereGuestCount($value)
     * @method static Builder|Booking whereGuestEmail($value)
     * @method static Builder|Booking whereGuestFirstName($value)
     * @method static Builder|Booking whereGuestLastName($value)
     * @method static Builder|Booking whereGuestPhone($value)
     * @method static Builder|Booking whereId($value)
     * @method static Builder|Booking whereInvoicePath($value)
     * @method static Builder|Booking whereIsPrime($value)
     * @method static Builder|Booking whereNoShow($value)
     * @method static Builder|Booking whereNotes($value)
     * @method static Builder|Booking wherePartnerConciergeFee($value)
     * @method static Builder|Booking wherePartnerConciergeId($value)
     * @method static Builder|Booking wherePartnerVenueFee($value)
     * @method static Builder|Booking wherePartnerVenueId($value)
     * @method static Builder|Booking wherePlatformEarnings($value)
     * @method static Builder|Booking whereResentVenueConfirmationAt($value)
     * @method static Builder|Booking whereVenueConfirmedAt($value)
     * @method static Builder|Booking whereVenueEarnings($value)
     * @method static Builder|Booking whereScheduleTemplateId($value)
     * @method static Builder|Booking whereStatus($value)
     * @method static Builder|Booking whereStripeCharge($value)
     * @method static Builder|Booking whereStripeChargeId($value)
     * @method static Builder|Booking whereTax($value)
     * @method static Builder|Booking whereTaxAmountInCents($value)
     * @method static Builder|Booking whereTotalFee($value)
     * @method static Builder|Booking whereTotalWithTaxInCents($value)
     * @method static Builder|Booking whereUpdatedAt($value)
     * @method static Builder|Booking whereUuid($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperBooking {}
}

namespace App\Models{use AllowDynamicProperties;use Database\Factories\ConciergeFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $user_id
     * @property string $hotel_name
     * @property string|null $hotel_phone
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Collection<int, Booking> $bookings
     * @property-read int|null $bookings_count
     * @property-read Collection<int, Concierge> $concierges
     * @property-read int|null $concierges_count
     * @property-read mixed $payout_percentage
     * @property-read Collection<int, Referral> $referrals
     * @property-read int|null $referrals_count
     * @property-read Concierge|null $referringConcierge
     * @property-read mixed $sales
     * @property-read mixed $sales_this_month
     * @property-read User $user
     *
     * @method static ConciergeFactory factory($count = null, $state = [])
     * @method static Builder|Concierge newModelQuery()
     * @method static Builder|Concierge newQuery()
     * @method static Builder|Concierge query()
     * @method static Builder|Concierge whereCreatedAt($value)
     * @method static Builder|Concierge whereHotelName($value)
     * @method static Builder|Concierge whereHotelPhone($value)
     * @method static Builder|Concierge whereId($value)
     * @method static Builder|Concierge whereUpdatedAt($value)
     * @method static Builder|Concierge whereUserId($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperConcierge {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $user_id
     * @property string $key
     * @property int $verified
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read User $user
     *
     * @method static Builder|Device newModelQuery()
     * @method static Builder|Device newQuery()
     * @method static Builder|Device query()
     * @method static Builder|Device whereCreatedAt($value)
     * @method static Builder|Device whereId($value)
     * @method static Builder|Device whereKey($value)
     * @method static Builder|Device whereUpdatedAt($value)
     * @method static Builder|Device whereUserId($value)
     * @method static Builder|Device whereVerified($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperDevice {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $user_id
     * @property int $booking_id
     * @property int|null $payment_id
     * @property string $type
     * @property int $amount
     * @property string $currency
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property string|null $confirmed_at
     * @property int $percentage
     * @property string $percentage_of
     * @property-read Booking $booking
     * @property-read Payment|null $payment
     * @property-read User $user
     *
     * @method static Builder|Earning confirmed()
     * @method static Builder|Earning newModelQuery()
     * @method static Builder|Earning newQuery()
     * @method static Builder|Earning query()
     * @method static Builder|Earning whereAmount($value)
     * @method static Builder|Earning whereBookingId($value)
     * @method static Builder|Earning whereConfirmedAt($value)
     * @method static Builder|Earning whereCreatedAt($value)
     * @method static Builder|Earning whereCurrency($value)
     * @method static Builder|Earning whereId($value)
     * @method static Builder|Earning wherePaymentId($value)
     * @method static Builder|Earning wherePercentage($value)
     * @method static Builder|Earning wherePercentageOf($value)
     * @method static Builder|Earning whereType($value)
     * @method static Builder|Earning whereUpdatedAt($value)
     * @method static Builder|Earning whereUserId($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperEarning {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $booking_id
     * @property string $error_message
     * @property int $venue_earnings
     * @property int $concierge_earnings
     * @property int $concierge_referral_level_1_earnings
     * @property int $concierge_referral_level_2_earnings
     * @property int $venue_partner_earnings
     * @property int $concierge_partner_earnings
     * @property int $platform_earnings
     * @property int $total_local
     * @property int $total_fee
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Booking $booking
     *
     * @method static Builder|EarningError newModelQuery()
     * @method static Builder|EarningError newQuery()
     * @method static Builder|EarningError query()
     * @method static Builder|EarningError whereBookingId($value)
     * @method static Builder|EarningError whereConciergeEarnings($value)
     * @method static Builder|EarningError whereConciergePartnerEarnings($value)
     * @method static Builder|EarningError whereConciergeReferralLevel1Earnings($value)
     * @method static Builder|EarningError whereConciergeReferralLevel2Earnings($value)
     * @method static Builder|EarningError whereCreatedAt($value)
     * @method static Builder|EarningError whereErrorMessage($value)
     * @method static Builder|EarningError whereId($value)
     * @method static Builder|EarningError wherePlatformEarnings($value)
     * @method static Builder|EarningError whereVenueEarnings($value)
     * @method static Builder|EarningError whereVenuePartnerEarnings($value)
     * @method static Builder|EarningError whereTotalFee($value)
     * @method static Builder|EarningError whereTotalLocal($value)
     * @method static Builder|EarningError whereUpdatedAt($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperEarningError {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $user_id
     * @property string $message
     * @property string|null $exception_message
     * @property string|null $exception_trace
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     *
     * @method static Builder|Feedback newModelQuery()
     * @method static Builder|Feedback newQuery()
     * @method static Builder|Feedback query()
     * @method static Builder|Feedback whereCreatedAt($value)
     * @method static Builder|Feedback whereExceptionMessage($value)
     * @method static Builder|Feedback whereExceptionTrace($value)
     * @method static Builder|Feedback whereId($value)
     * @method static Builder|Feedback whereMessage($value)
     * @method static Builder|Feedback whereUpdatedAt($value)
     * @method static Builder|Feedback whereUserId($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperFeedback {}
}

namespace App\Models{use AllowDynamicProperties;use Database\Factories\MessageFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $user_id
     * @property int $announcement_id
     * @property string|null $read_at
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Announcement $announcement
     * @property-read User|null $recipient
     *
     * @method static MessageFactory factory($count = null, $state = [])
     * @method static Builder|Message newModelQuery()
     * @method static Builder|Message newQuery()
     * @method static Builder|Message query()
     * @method static Builder|Message whereAnnouncementId($value)
     * @method static Builder|Message whereCreatedAt($value)
     * @method static Builder|Message whereId($value)
     * @method static Builder|Message whereReadAt($value)
     * @method static Builder|Message whereUpdatedAt($value)
     * @method static Builder|Message whereUserId($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperMessage {}
}

namespace App\Models{use AllowDynamicProperties;use Database\Factories\PartnerFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $user_id
     * @property int $percentage
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Collection<int, Booking> $conciergeBookings
     * @property-read int|null $concierge_bookings_count
     * @property-read Collection<int, Booking> $venueBookings
     * @property-read int|null $venue_bookings_count
     * @property-read User $user
     *
     * @method static PartnerFactory factory($count = null, $state = [])
     * @method static Builder|Partner newModelQuery()
     * @method static Builder|Partner newQuery()
     * @method static Builder|Partner query()
     * @method static Builder|Partner whereCreatedAt($value)
     * @method static Builder|Partner whereId($value)
     * @method static Builder|Partner wherePercentage($value)
     * @method static Builder|Partner whereUpdatedAt($value)
     * @method static Builder|Partner whereUserId($value)
     * @method static Builder|Partner withAllBookings()
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperPartner {}
}

namespace App\Models{use AllowDynamicProperties;use App\Enums\PaymentStatus;use Database\Factories\PaymentFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property string $title
     * @property int $amount
     * @property string $currency
     * @property PaymentStatus $status
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Collection<int, Earning> $earnings
     * @property-read int|null $earnings_count
     * @property-read Collection<int, PaymentItem> $items
     * @property-read int|null $items_count
     *
     * @method static PaymentFactory factory($count = null, $state = [])
     * @method static Builder|Payment newModelQuery()
     * @method static Builder|Payment newQuery()
     * @method static Builder|Payment query()
     * @method static Builder|Payment whereAmount($value)
     * @method static Builder|Payment whereCreatedAt($value)
     * @method static Builder|Payment whereCurrency($value)
     * @method static Builder|Payment whereId($value)
     * @method static Builder|Payment whereStatus($value)
     * @method static Builder|Payment whereTitle($value)
     * @method static Builder|Payment whereUpdatedAt($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperPayment {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $user_id
     * @property int $payment_id
     * @property string $currency
     * @property int $amount
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Payment $payment
     * @property-read User $user
     *
     * @method static Builder|PaymentItem newModelQuery()
     * @method static Builder|PaymentItem newQuery()
     * @method static Builder|PaymentItem query()
     * @method static Builder|PaymentItem whereAmount($value)
     * @method static Builder|PaymentItem whereCreatedAt($value)
     * @method static Builder|PaymentItem whereCurrency($value)
     * @method static Builder|PaymentItem whereId($value)
     * @method static Builder|PaymentItem wherePaymentId($value)
     * @method static Builder|PaymentItem whereUpdatedAt($value)
     * @method static Builder|PaymentItem whereUserId($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperPaymentItem {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Notifications\DatabaseNotification;use Illuminate\Notifications\DatabaseNotificationCollection;use Illuminate\Support\Carbon;

    /**
     * @property string $id
     * @property int $referrer_id
     * @property string|null $email
     * @property string|null $phone
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
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
     * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
     * @property-read int|null $notifications_count
     * @property-read User $referrer
     * @property-read User|null $user
     *
     * @method static Builder|Referral newModelQuery()
     * @method static Builder|Referral newQuery()
     * @method static Builder|Referral query()
     * @method static Builder|Referral whereCreatedAt($value)
     * @method static Builder|Referral whereEmail($value)
     * @method static Builder|Referral whereFirstName($value)
     * @method static Builder|Referral whereId($value)
     * @method static Builder|Referral whereLastName($value)
     * @method static Builder|Referral whereNotifiedAt($value)
     * @method static Builder|Referral wherePhone($value)
     * @method static Builder|Referral whereReferrerId($value)
     * @method static Builder|Referral whereReferrerType($value)
     * @method static Builder|Referral whereSecuredAt($value)
     * @method static Builder|Referral whereType($value)
     * @method static Builder|Referral whereUpdatedAt($value)
     * @method static Builder|Referral whereUserId($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperReferral {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;

    /**
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
     * @property-read Collection<int, Venue> $venues
     * @property-read int|null $venues_count
     *
     * @method static Builder|Region active()
     * @method static Builder|Region newModelQuery()
     * @method static Builder|Region newQuery()
     * @method static Builder|Region query()
     * @method static Builder|Region whereCountry($value)
     * @method static Builder|Region whereCurrency($value)
     * @method static Builder|Region whereCurrencySymbol($value)
     * @method static Builder|Region whereId($value)
     * @method static Builder|Region whereLat($value)
     * @method static Builder|Region whereLon($value)
     * @method static Builder|Region whereName($value)
     * @method static Builder|Region whereTaxRate($value)
     * @method static Builder|Region whereTaxRateTerm($value)
     * @method static Builder|Region whereTimezone($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperRegion {}
}

namespace App\Models{use AllowDynamicProperties;use App\Enums\VenueStatus;use Database\Factories\VenueFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;use Spatie\LaravelData\DataCollection;

    /**
     * Class Venue
     *
     * @property int $id
     * @property int $user_id
     * @property string $name
     * @property string $slug
     * @property string $contact_phone
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property int $payout_venue
     * @property string|null $primary_contact_name
     * @property int $booking_fee
     * @property int $increment_fee
     * @property int $non_prime_fee_per_head
     * @property string $non_prime_type
     * @property array|null $open_days
     * @property DataCollection|null $contacts
     * @property int $is_suspended
     * @property array|null $non_prime_time
     * @property array|null $business_hours
     * @property array|null $party_sizes
     * @property int|null $minimum_spend
     * @property string $region
     * @property string|null $venue_logo_path
     * @property VenueStatus $status
     * @property-read Region|null $inRegion
     * @property-read mixed $logo
     * @property-read mixed $name
     * @property-read Partner|null $partnerReferral
     * @property-read Collection<int, ScheduleTemplate> $scheduleTemplates
     * @property-read int|null $schedule_templates_count
     * @property-read Collection<int, ScheduleWithBooking> $schedules
     * @property-read int|null $schedules_count
     * @property-read Collection<int, SpecialPricingVenue> $specialPricing
     * @property-read int|null $special_pricing_count
     * @property-read Collection<int, VenueTimeSlot> $timeSlots
     * @property-read int|null $time_slots_count
     * @property-read User $user
     *
     * @method static Builder|Venue available()
     * @method static VenueFactory factory($count = null, $state = [])
     * @method static Builder|Venue newModelQuery()
     * @method static Builder|Venue newQuery()
     * @method static Builder|Venue query()
     * @method static Builder|Venue whereBookingFee($value)
     * @method static Builder|Venue whereBusinessHours($value)
     * @method static Builder|Venue whereContactPhone($value)
     * @method static Builder|Venue whereContacts($value)
     * @method static Builder|Venue whereCreatedAt($value)
     * @method static Builder|Venue whereId($value)
     * @method static Builder|Venue whereIncrementFee($value)
     * @method static Builder|Venue whereIsSuspended($value)
     * @method static Builder|Venue whereMinimumSpend($value)
     * @method static Builder|Venue whereNonPrimeFeePerHead($value)
     * @method static Builder|Venue whereNonPrimeTime($value)
     * @method static Builder|Venue whereNonPrimeType($value)
     * @method static Builder|Venue whereOpenDays($value)
     * @method static Builder|Venue wherePartySizes($value)
     * @method static Builder|Venue wherePayoutVenue($value)
     * @method static Builder|Venue wherePrimaryContactName($value)
     * @method static Builder|Venue whereRegion($value)
     * @method static Builder|Venue whereVenueLogoPath($value)
     * @method static Builder|Venue whereVenueName($value)
     * @method static Builder|Venue whereSlug($value)
     * @method static Builder|Venue whereStatus($value)
     * @method static Builder|Venue whereUpdatedAt($value)
     * @method static Builder|Venue whereUserId($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperVenue {}
}

namespace App\Models{use AllowDynamicProperties;use Database\Factories\VenueTimeSlotFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

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
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read ScheduleTemplate $scheduleTemplate
     * @property-read Venue|null $venue
     *
     * @method static VenueTimeSlotFactory factory($count = null, $state = [])
     * @method static Builder|VenueTimeSlot newModelQuery()
     * @method static Builder|VenueTimeSlot newQuery()
     * @method static Builder|VenueTimeSlot query()
     * @method static Builder|VenueTimeSlot whereBookingDate($value)
     * @method static Builder|VenueTimeSlot whereCreatedAt($value)
     * @method static Builder|VenueTimeSlot whereId($value)
     * @method static Builder|VenueTimeSlot wherePrimeTime($value)
     * @method static Builder|VenueTimeSlot wherePrimeTimeFee($value)
     * @method static Builder|VenueTimeSlot whereScheduleTemplateId($value)
     * @method static Builder|VenueTimeSlot whereUpdatedAt($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperVenueTimeSlot {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
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
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Venue $venue
     *
     * @method static Builder|ScheduleTemplate newModelQuery()
     * @method static Builder|ScheduleTemplate newQuery()
     * @method static Builder|ScheduleTemplate query()
     * @method static Builder|ScheduleTemplate whereAvailableTables($value)
     * @method static Builder|ScheduleTemplate whereCreatedAt($value)
     * @method static Builder|ScheduleTemplate whereDayOfWeek($value)
     * @method static Builder|ScheduleTemplate whereEndTime($value)
     * @method static Builder|ScheduleTemplate whereId($value)
     * @method static Builder|ScheduleTemplate whereIsAvailable($value)
     * @method static Builder|ScheduleTemplate wherePartySize($value)
     * @method static Builder|ScheduleTemplate wherePrimeTime($value)
     * @method static Builder|ScheduleTemplate wherePrimeTimeFee($value)
     * @method static Builder|ScheduleTemplate whereVenueId($value)
     * @method static Builder|ScheduleTemplate whereStartTime($value)
     * @method static Builder|ScheduleTemplate whereUpdatedAt($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperScheduleTemplate {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;

    /**
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
     * @property-read Collection<int, Booking> $bookings
     * @property-read int|null $bookings_count
     * @property-read mixed $formatted_end_time
     * @property-read mixed $formatted_start_time
     * @property-read mixed $has_low_inventory
     * @property-read Venue|null $venue
     *
     * @method static Builder|ScheduleWithBooking newModelQuery()
     * @method static Builder|ScheduleWithBooking newQuery()
     * @method static Builder|ScheduleWithBooking query()
     * @method static Builder|ScheduleWithBooking whereAvailableTables($value)
     * @method static Builder|ScheduleWithBooking whereBookingAt($value)
     * @method static Builder|ScheduleWithBooking whereBookingDate($value)
     * @method static Builder|ScheduleWithBooking whereDayOfWeek($value)
     * @method static Builder|ScheduleWithBooking whereEffectiveFee($value)
     * @method static Builder|ScheduleWithBooking whereEndTime($value)
     * @method static Builder|ScheduleWithBooking whereId($value)
     * @method static Builder|ScheduleWithBooking whereIsAvailable($value)
     * @method static Builder|ScheduleWithBooking wherePartySize($value)
     * @method static Builder|ScheduleWithBooking wherePrimeTime($value)
     * @method static Builder|ScheduleWithBooking wherePrimeTimeFee($value)
     * @method static Builder|ScheduleWithBooking whereRemainingTables($value)
     * @method static Builder|ScheduleWithBooking whereVenueId($value)
     * @method static Builder|ScheduleWithBooking whereScheduleEnd($value)
     * @method static Builder|ScheduleWithBooking whereScheduleStart($value)
     * @method static Builder|ScheduleWithBooking whereScheduleTemplateId($value)
     * @method static Builder|ScheduleWithBooking whereStartTime($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperScheduleWithBooking {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property string $phone_number
     * @property string $message
     * @property string $response
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Booking|null $booking
     *
     * @method static Builder|SmsResponse newModelQuery()
     * @method static Builder|SmsResponse newQuery()
     * @method static Builder|SmsResponse query()
     * @method static Builder|SmsResponse whereCreatedAt($value)
     * @method static Builder|SmsResponse whereId($value)
     * @method static Builder|SmsResponse whereMessage($value)
     * @method static Builder|SmsResponse wherePhoneNumber($value)
     * @method static Builder|SmsResponse whereResponse($value)
     * @method static Builder|SmsResponse whereUpdatedAt($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperSmsResponse {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $venue_id
     * @property string $date
     * @property int $fee
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read Venue $venue
     *
     * @method static Builder|SpecialPricingVenue newModelQuery()
     * @method static Builder|SpecialPricingVenue newQuery()
     * @method static Builder|SpecialPricingVenue query()
     * @method static Builder|SpecialPricingVenue whereCreatedAt($value)
     * @method static Builder|SpecialPricingVenue whereDate($value)
     * @method static Builder|SpecialPricingVenue whereFee($value)
     * @method static Builder|SpecialPricingVenue whereId($value)
     * @method static Builder|SpecialPricingVenue whereVenueId($value)
     * @method static Builder|SpecialPricingVenue whereUpdatedAt($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperSpecialPricingVenue {}
}

namespace App\Models{use AllowDynamicProperties;use App\Enums\SpecialRequestStatus;use ArrayObject;use Database\Factories\SpecialRequestFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;use Spatie\LaravelData\DataCollection;

    /**
     * @property string $uuid
     * @property int $id
     * @property int $venue_id
     * @property int $concierge_id
     * @property Carbon $booking_date
     * @property Carbon $booking_time
     * @property int $party_size
     * @property string|null $special_request
     * @property string $customer_first_name
     * @property string $customer_last_name
     * @property string $customer_phone
     * @property string|null $customer_email
     * @property int $commission_requested_percentage
     * @property int $minimum_spend
     * @property SpecialRequestStatus $status
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property int|null $schedule_template_id
     * @property int|null $booking_id
     * @property string|null $venue_message
     * @property DataCollection|null $conversations
     * @property ArrayObject|null $meta
     * @property-read Booking|null $booking
     * @property-read Concierge $concierge
     * @property-read mixed $customer_name
     * @property-read Venue $venue
     * @property-read mixed $venue_total_fee
     * @property-read ScheduleTemplate|null $scheduleTemplate
     *
     * @method static SpecialRequestFactory factory($count = null, $state = [])
     * @method static Builder|SpecialRequest newModelQuery()
     * @method static Builder|SpecialRequest newQuery()
     * @method static Builder|SpecialRequest query()
     * @method static Builder|SpecialRequest whereBookingDate($value)
     * @method static Builder|SpecialRequest whereBookingId($value)
     * @method static Builder|SpecialRequest whereBookingTime($value)
     * @method static Builder|SpecialRequest whereCommissionRequestedPercentage($value)
     * @method static Builder|SpecialRequest whereConciergeId($value)
     * @method static Builder|SpecialRequest whereConversations($value)
     * @method static Builder|SpecialRequest whereCreatedAt($value)
     * @method static Builder|SpecialRequest whereCustomerEmail($value)
     * @method static Builder|SpecialRequest whereCustomerFirstName($value)
     * @method static Builder|SpecialRequest whereCustomerLastName($value)
     * @method static Builder|SpecialRequest whereCustomerPhone($value)
     * @method static Builder|SpecialRequest whereId($value)
     * @method static Builder|SpecialRequest whereMeta($value)
     * @method static Builder|SpecialRequest whereMinimumSpend($value)
     * @method static Builder|SpecialRequest wherePartySize($value)
     * @method static Builder|SpecialRequest whereVenueId($value)
     * @method static Builder|SpecialRequest whereVenueMessage($value)
     * @method static Builder|SpecialRequest whereScheduleTemplateId($value)
     * @method static Builder|SpecialRequest whereSpecialRequest($value)
     * @method static Builder|SpecialRequest whereStatus($value)
     * @method static Builder|SpecialRequest whereUpdatedAt($value)
     * @method static Builder|SpecialRequest whereUuid($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperSpecialRequest {}
}

namespace App\Models{use AllowDynamicProperties;use ArrayObject;use Database\Factories\UserFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Notifications\DatabaseNotification;use Illuminate\Notifications\DatabaseNotificationCollection;use Illuminate\Support\Carbon;use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;use Spatie\LaravelData\Contracts\BaseData;use Spatie\Permission\Models\Permission;use Spatie\Permission\Models\Role;

    /**
     * @property int $id
     * @property string $first_name
     * @property string $last_name
     * @property string $email
     * @property Carbon|null $email_verified_at
     * @property string $password
     * @property string|null $two_factor_secret
     * @property string|null $two_factor_recovery_codes
     * @property string|null $two_factor_confirmed_at
     * @property string|null $remember_token
     * @property int|null $current_team_id
     * @property string|null $profile_photo_path
     * @property Carbon|null $created_at
     * @property Carbon|null $secured_at
     * @property Carbon|null $updated_at
     * @property string|null $stripe_id
     * @property string|null $pm_type
     * @property string|null $pm_last_four
     * @property string|null $trial_ends_at
     * @property string|null $phone
     * @property ArrayObject|null $payout
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
     * @property BaseData|null $preferences
     * @property string $userable_type
     * @property int $userable_id
     * @property-read Collection<int, AuthenticationLog> $authentications
     * @property-read int|null $authentications_count
     * @property-read mixed $avatar
     * @property-read Concierge|null $concierge
     * @property-read Collection<int, Device> $devices
     * @property-read int|null $devices_count
     * @property-read Collection<int, Earning> $earnings
     * @property-read int|null $earnings_count
     * @property-read mixed $has_secured
     * @property-read mixed $international_formatted_phone_number
     * @property-read mixed $label
     * @property-read AuthenticationLog|null $latestAuthentication
     * @property-read mixed $local_formatted_phone
     * @property-read mixed $main_role
     * @property-read Collection<int, Message> $messages
     * @property-read int|null $messages_count
     * @property-read mixed $name
     * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
     * @property-read int|null $notifications_count
     * @property-read Partner|null $partner
     * @property-read Collection<int, Permission> $permissions
     * @property-read int|null $permissions_count
     * @property-read Referral|null $referral
     * @property-read Collection<int, Referral> $referrals
     * @property-read int|null $referrals_count
     * @property-read User|null $referrer
     * @property-read Venue|null $venue
     * @property-read Collection<int, Role> $roles
     * @property-read int|null $roles_count
     * @property-read Collection<int, Announcement> $sentAnnouncements
     * @property-read int|null $sent_announcements_count
     * @property-read mixed $unread_message_count
     * @property-read UserCode|null $userCode
     *
     * @method static UserFactory factory($count = null, $state = [])
     * @method static Builder|User newModelQuery()
     * @method static Builder|User newQuery()
     * @method static Builder|User permission($permissions, $without = false)
     * @method static Builder|User query()
     * @method static Builder|User role($roles, $guard = null, $without = false)
     * @method static Builder|User whereAddress1($value)
     * @method static Builder|User whereAddress2($value)
     * @method static Builder|User whereCharityPercentage($value)
     * @method static Builder|User whereCity($value)
     * @method static Builder|User whereConciergeReferralId($value)
     * @method static Builder|User whereCountry($value)
     * @method static Builder|User whereCreatedAt($value)
     * @method static Builder|User whereCurrentTeamId($value)
     * @method static Builder|User whereEmail($value)
     * @method static Builder|User whereEmailVerifiedAt($value)
     * @method static Builder|User whereFirstName($value)
     * @method static Builder|User whereId($value)
     * @method static Builder|User whereLastName($value)
     * @method static Builder|User wherePartnerReferralId($value)
     * @method static Builder|User wherePassword($value)
     * @method static Builder|User wherePayout($value)
     * @method static Builder|User wherePhone($value)
     * @method static Builder|User wherePmLastFour($value)
     * @method static Builder|User wherePmType($value)
     * @method static Builder|User wherePreferences($value)
     * @method static Builder|User whereProfilePhotoPath($value)
     * @method static Builder|User whereRegion($value)
     * @method static Builder|User whereRememberToken($value)
     * @method static Builder|User whereSecuredAt($value)
     * @method static Builder|User whereState($value)
     * @method static Builder|User whereStripeId($value)
     * @method static Builder|User whereTimezone($value)
     * @method static Builder|User whereTrialEndsAt($value)
     * @method static Builder|User whereTwoFactorConfirmedAt($value)
     * @method static Builder|User whereTwoFactorRecoveryCodes($value)
     * @method static Builder|User whereTwoFactorSecret($value)
     * @method static Builder|User whereUpdatedAt($value)
     * @method static Builder|User whereUserableId($value)
     * @method static Builder|User whereUserableType($value)
     * @method static Builder|User whereZip($value)
     * @method static Builder|User withoutPermission($permissions)
     * @method static Builder|User withoutRole($roles, $guard = null)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperUser {}
}

namespace App\Models{use AllowDynamicProperties;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;

    /**
     * @property int $id
     * @property int $user_id
     * @property string $code
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read User $user
     *
     * @method static Builder|UserCode newModelQuery()
     * @method static Builder|UserCode newQuery()
     * @method static Builder|UserCode query()
     * @method static Builder|UserCode whereCode($value)
     * @method static Builder|UserCode whereCreatedAt($value)
     * @method static Builder|UserCode whereId($value)
     * @method static Builder|UserCode whereUpdatedAt($value)
     * @method static Builder|UserCode whereUserId($value)
     *
     * @mixin Eloquent
     */
    #[AllowDynamicProperties]
    class IdeHelperUserCode {}
}
