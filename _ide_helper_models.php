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
 * @property string $uuid
 * @property int $concierge_id
 * @property int|null $partner_concierge_id
 * @property int|null $partner_restaurant_id
 * @property string|null $guest_first_name
 * @property string|null $guest_last_name
 * @property string|null $guest_email
 * @property string|null $guest_phone
 * @property \Illuminate\Support\Carbon $booking_at
 * @property int $guest_count
 * @property int $total_fee
 * @property string $currency
 * @property \App\Enums\BookingStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $schedule_id
 * @property string|null $stripe_charge_id
 * @property \Spatie\LaravelData\Contracts\BaseData|null $stripe_charge
 * @property int $restaurant_earnings
 * @property int $concierge_earnings
 * @property int $charity_earnings
 * @property int $platform_earnings
 * @property int $partner_concierge_fee
 * @property int $partner_restaurant_fee
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property string|null $concierge_referral_type
 * @property \Illuminate\Support\Carbon|null $restaurant_confirmed_at
 * @property \Illuminate\Support\Carbon|null $resent_restaurant_confirmation_at
 * @property int|null $tax_amount_in_cents
 * @property float|null $tax
 * @property int|null $total_with_tax_in_cents
 * @property string|null $city
 * @property string|null $invoice_path
 * @property-read \App\Models\Concierge $concierge
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Earning> $earnings
 * @property-read int|null $earnings_count
 * @property-read string $guest_name
 * @property-read string $local_formatted_guest_phone
 * @property-read mixed $partner_earnings
 * @property-read \App\Models\Partner|null $partnerConcierge
 * @property-read \App\Models\Partner|null $partnerRestaurant
 * @property-read \App\Models\Restaurant|null $restaurant
 * @property-read \App\Models\Schedule $schedule
 * @method static \Illuminate\Database\Eloquent\Builder|Booking confirmed()
 * @method static \Database\Factories\BookingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Booking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Booking newQuery()
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
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePartnerConciergeFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePartnerConciergeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePartnerRestaurantFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePartnerRestaurantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking wherePlatformEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereResentRestaurantConfirmationAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereRestaurantConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereRestaurantEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereStripeCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereStripeChargeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTaxAmountInCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTotalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereTotalWithTaxInCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Booking whereUuid($value)
 */
	class Booking extends \Eloquent {}
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
 * @property-read int $payout_percentage
 * @property-read int $sales
 * @property-read Concierge|null $referringConcierge
 * @property-read \App\Models\User $user
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
 */
	class Concierge extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $booking_id
 * @property string $type
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $confirmed_at
 * @property int $percentage
 * @property string $percentage_of
 * @property-read \App\Models\Booking $booking
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Earning confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder|Earning newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Earning newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Earning query()
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning wherePercentageOf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Earning whereUserId($value)
 */
	class Earning extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $booking_id
 * @property string $error_message
 * @property int $restaurant_earnings
 * @property int $concierge_earnings
 * @property int $concierge_referral_level_1_earnings
 * @property int $concierge_referral_level_2_earnings
 * @property int $restaurant_partner_earnings
 * @property int $concierge_partner_earnings
 * @property int $platform_earnings
 * @property int $total_local
 * @property int $total_fee
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Booking $booking
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError query()
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereConciergeEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereConciergePartnerEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereConciergeReferralLevel1Earnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereConciergeReferralLevel2Earnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError wherePlatformEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereRestaurantEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereRestaurantPartnerEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereTotalFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereTotalLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningError whereUpdatedAt($value)
 */
	class EarningError extends \Eloquent {}
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $restaurantBookings
 * @property-read int|null $restaurant_bookings_count
 * @property-read \App\Models\User $user
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
 */
	class Partner extends \Eloquent {}
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
 * @property-read bool $has_secured
 * @property-read string $label
 * @property-read string $local_formatted_phone
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\User $referrer
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Referral newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral query()
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereReferrerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereReferrerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereSecuredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Referral whereUserId($value)
 */
	class Referral extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Restaurant
 *
 * @property int $id
 * @property int $user_id
 * @property string $restaurant_name
 * @property string $contact_phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $payout_restaurant
 * @property string|null $primary_contact_name
 * @property int $booking_fee
 * @property array|null $open_days
 * @property \Spatie\LaravelData\DataCollection|null $contacts
 * @property int $is_suspended
 * @property array|null $non_prime_time
 * @property array|null $business_hours
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Schedule> $availableSchedules
 * @property-read int|null $available_schedules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Schedule> $schedules
 * @property-read int|null $schedules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SpecialPricingRestaurant> $specialPricing
 * @property-read int|null $special_pricing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Schedule> $unavailableSchedules
 * @property-read int|null $unavailable_schedules_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant available()
 * @method static \Database\Factories\RestaurantFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant openOnDate(string $date)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant openToday()
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant query()
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereBookingFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereBusinessHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereContacts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereIsSuspended($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereNonPrimeTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereOpenDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant wherePayoutRestaurant($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant wherePrimaryContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereRestaurantName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Restaurant whereUserId($value)
 */
	class Restaurant extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $restaurant_id
 * @property string|null $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property bool $is_available
 * @property int $available_tables
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property mixed $0
 * @property mixed $1
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read int $computed_available_tables
 * @property-read string $formatted_end_time
 * @property-read string $formatted_start_time
 * @property-read bool $is_bookable
 * @property-read \App\Models\Restaurant $restaurant
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule available()
 * @method static \Database\Factories\ScheduleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule query()
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule unavailable()
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereAvailableTables($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereRestaurantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schedule whereUpdatedAt($value)
 */
	class Schedule extends \Eloquent {}
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
 */
	class SmsResponse extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $restaurant_id
 * @property string $date
 * @property int $fee
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Restaurant $restaurant
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant query()
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant whereRestaurantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SpecialPricingRestaurant whereUpdatedAt($value)
 */
	class SpecialPricingRestaurant extends \Eloquent {}
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
 * @property-read \App\Models\Concierge|null $concierge
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Earning> $earnings
 * @property-read int|null $earnings_count
 * @property-read bool $has_secured
 * @property-read string $label
 * @property-read string $main_role
 * @property-read string $name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Partner|null $partner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \App\Models\Referral|null $referral
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Referral> $referrals
 * @property-read int|null $referrals_count
 * @property-read User|null $referrer
 * @property-read \App\Models\Restaurant|null $restaurant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
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
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfilePhotoPath($value)
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
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser, \Filament\Models\Contracts\HasAvatar {}
}

