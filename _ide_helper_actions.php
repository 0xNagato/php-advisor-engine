<?php

namespace App\Actions\Booking;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(string $phoneNumber, string $bookingDate, ?string $timezone = null)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(string $phoneNumber, string $bookingDate, ?string $timezone = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(string $phoneNumber, string $bookingDate, ?string $timezone = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, string $phoneNumber, string $bookingDate, ?string $timezone = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, string $phoneNumber, string $bookingDate, ?string $timezone = null)
 * @method static dispatchSync(string $phoneNumber, string $bookingDate, ?string $timezone = null)
 * @method static dispatchNow(string $phoneNumber, string $bookingDate, ?string $timezone = null)
 * @method static dispatchAfterResponse(string $phoneNumber, string $bookingDate, ?string $timezone = null)
 * @method static \App\Models\Booking|null run(string $phoneNumber, string $bookingDate, ?string $timezone = null)
 */
class CheckCustomerHasNonPrimeBooking
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\Booking $booking, string $paymentIntentId, array $formData)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\Booking $booking, string $paymentIntentId, array $formData)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\Booking $booking, string $paymentIntentId, array $formData)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\Booking $booking, string $paymentIntentId, array $formData)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\Booking $booking, string $paymentIntentId, array $formData)
 * @method static dispatchSync(\App\Models\Booking $booking, string $paymentIntentId, array $formData)
 * @method static dispatchNow(\App\Models\Booking $booking, string $paymentIntentId, array $formData)
 * @method static dispatchAfterResponse(\App\Models\Booking $booking, string $paymentIntentId, array $formData)
 * @method static array run(\App\Models\Booking $booking, string $paymentIntentId, array $formData)
 */
class CompleteBooking
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\Booking $booking)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\Booking $booking)
 * @method static dispatchSync(\App\Models\Booking $booking)
 * @method static dispatchNow(\App\Models\Booking $booking)
 * @method static dispatchAfterResponse(\App\Models\Booking $booking)
 * @method static array run(\App\Models\Booking $booking)
 */
class ConvertToNonPrime
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\Booking $booking)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\Booking $booking)
 * @method static dispatchSync(\App\Models\Booking $booking)
 * @method static dispatchNow(\App\Models\Booking $booking)
 * @method static dispatchAfterResponse(\App\Models\Booking $booking)
 * @method static array run(\App\Models\Booking $booking)
 */
class ConvertToPrime
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 * @method static dispatchSync(int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 * @method static dispatchNow(int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 * @method static dispatchAfterResponse(int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 * @method static \App\Data\Booking\CreateBookingReturnData run(int $scheduleTemplateId, array $data, string $timezone, string $currency, ?\App\Models\VipCode $vipCode = null)
 */
class CreateBooking
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, ?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, ?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 * @method static dispatchSync(?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 * @method static dispatchNow(?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 * @method static dispatchAfterResponse(?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 * @method static string run(?string $startDate = null, ?string $endDate = null, int $daysToGenerate = 4, ?\Illuminate\Console\Command $command = null)
 */
class GenerateDemoBookings
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\Booking $booking)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\Booking $booking)
 * @method static dispatchSync(\App\Models\Booking $booking)
 * @method static dispatchNow(\App\Models\Booking $booking)
 * @method static dispatchAfterResponse(\App\Models\Booking $booking)
 * @method static void run(\App\Models\Booking $booking)
 */
class NotifyAdminsOfUnconfirmedBooking
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 * @method static dispatchSync(\App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 * @method static dispatchNow(\App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 * @method static dispatchAfterResponse(\App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 * @method static array run(\App\Models\Booking $booking, ?string $reason = null, ?int $amount = null)
 */
class RefundBooking
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\Booking $booking)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\Booking $booking)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\Booking $booking)
 * @method static dispatchSync(\App\Models\Booking $booking)
 * @method static dispatchNow(\App\Models\Booking $booking)
 * @method static dispatchAfterResponse(\App\Models\Booking $booking)
 * @method static void run(\App\Models\Booking $booking)
 */
class SendConfirmationToVenueContacts
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\BookingModificationRequest $modificationRequest)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\BookingModificationRequest $modificationRequest)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\BookingModificationRequest $modificationRequest)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\BookingModificationRequest $modificationRequest)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\BookingModificationRequest $modificationRequest)
 * @method static dispatchSync(\App\Models\BookingModificationRequest $modificationRequest)
 * @method static dispatchNow(\App\Models\BookingModificationRequest $modificationRequest)
 * @method static dispatchAfterResponse(\App\Models\BookingModificationRequest $modificationRequest)
 * @method static void run(\App\Models\BookingModificationRequest $modificationRequest)
 */
class SendModificationRequestToVenueContacts
{
}
namespace App\Actions\Concierge;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\Concierge $concierge)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\Concierge $concierge)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\Concierge $concierge)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\Concierge $concierge)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\Concierge $concierge)
 * @method static dispatchSync(\App\Models\Concierge $concierge)
 * @method static dispatchNow(\App\Models\Concierge $concierge)
 * @method static dispatchAfterResponse(\App\Models\Concierge $concierge)
 * @method static void run(\App\Models\Concierge $concierge)
 */
class EnsureVipCodeExists
{
}
namespace App\Actions;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\VenueOnboarding $onboarding)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\VenueOnboarding $onboarding)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\VenueOnboarding $onboarding)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\VenueOnboarding $onboarding)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\VenueOnboarding $onboarding)
 * @method static dispatchSync(\App\Models\VenueOnboarding $onboarding)
 * @method static dispatchNow(\App\Models\VenueOnboarding $onboarding)
 * @method static dispatchAfterResponse(\App\Models\VenueOnboarding $onboarding)
 * @method static string run(\App\Models\VenueOnboarding $onboarding)
 */
class GenerateVenueAgreement
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(array $data, mixed $user)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(array $data, mixed $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(array $data, mixed $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, array $data, mixed $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, array $data, mixed $user)
 * @method static dispatchSync(array $data, mixed $user)
 * @method static dispatchNow(array $data, mixed $user)
 * @method static dispatchAfterResponse(array $data, mixed $user)
 * @method static mixed run(array $data, mixed $user)
 */
class SendContactFormEmail
{
}
namespace App\Actions\Partner;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(array $data)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(array $data)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(array $data)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, array $data)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, array $data)
 * @method static dispatchSync(array $data)
 * @method static dispatchNow(array $data)
 * @method static dispatchAfterResponse(array $data)
 * @method static \App\Models\Referral run(array $data)
 */
class InviteConciergeViaSms
{
}
namespace App\Actions\Region;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob()
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob()
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch()
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean)
 * @method static dispatchSync()
 * @method static dispatchNow()
 * @method static dispatchAfterResponse()
 * @method static \App\Models\Region run()
 */
class GetUserRegion
{
}
namespace App\Actions\Reservations;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(string $date, mixed $onlyShowFuture = false)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(string $date, mixed $onlyShowFuture = false)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(string $date, mixed $onlyShowFuture = false)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, string $date, mixed $onlyShowFuture = false)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, string $date, mixed $onlyShowFuture = false)
 * @method static dispatchSync(string $date, mixed $onlyShowFuture = false)
 * @method static dispatchNow(string $date, mixed $onlyShowFuture = false)
 * @method static dispatchAfterResponse(string $date, mixed $onlyShowFuture = false)
 * @method static array run(string $date, mixed $onlyShowFuture = false)
 */
class GetReservationTimeOptions
{
}
namespace App\Actions\SpecialRequest;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Data\SpecialRequest\CreateSpecialRequestData $data)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Data\SpecialRequest\CreateSpecialRequestData $data)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Data\SpecialRequest\CreateSpecialRequestData $data)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Data\SpecialRequest\CreateSpecialRequestData $data)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Data\SpecialRequest\CreateSpecialRequestData $data)
 * @method static dispatchSync(\App\Data\SpecialRequest\CreateSpecialRequestData $data)
 * @method static dispatchNow(\App\Data\SpecialRequest\CreateSpecialRequestData $data)
 * @method static dispatchAfterResponse(\App\Data\SpecialRequest\CreateSpecialRequestData $data)
 * @method static \App\Models\SpecialRequest run(\App\Data\SpecialRequest\CreateSpecialRequestData $data)
 */
class CreateSpecialRequest
{
}
namespace App\Actions\User;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\User $user)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\User $user)
 * @method static dispatchSync(\App\Models\User $user)
 * @method static dispatchNow(\App\Models\User $user)
 * @method static dispatchAfterResponse(\App\Models\User $user)
 * @method static bool run(\App\Models\User $user)
 */
class CheckUserHasBookings
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\User $user)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\User $user)
 * @method static dispatchSync(\App\Models\User $user)
 * @method static dispatchNow(\App\Models\User $user)
 * @method static dispatchAfterResponse(\App\Models\User $user)
 * @method static array run(\App\Models\User $user)
 */
class DeleteOrSuspendUser
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(\App\Models\User $user)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(\App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(\App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, \App\Models\User $user)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, \App\Models\User $user)
 * @method static dispatchSync(\App\Models\User $user)
 * @method static dispatchNow(\App\Models\User $user)
 * @method static dispatchAfterResponse(\App\Models\User $user)
 * @method static array run(\App\Models\User $user)
 */
class SuspendUser
{
}
namespace App\Actions\Venue;

/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(string $text)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(string $text)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(string $text)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, string $text)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, string $text)
 * @method static dispatchSync(string $text)
 * @method static dispatchNow(string $text)
 * @method static dispatchAfterResponse(string $text)
 * @method static array run(string $text)
 */
class ParseVenueScheduleWithClaude
{
}
namespace Lorisleiva\Actions\Concerns;

/**
 * @method void asController()
 */
trait AsController
{
}
/**
 * @method void asListener()
 */
trait AsListener
{
}
/**
 * @method void asJob()
 */
trait AsJob
{
}
/**
 * @method void asCommand(\Illuminate\Console\Command $command)
 */
trait AsCommand
{
}