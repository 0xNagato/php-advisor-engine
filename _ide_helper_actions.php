<?php

namespace App\Actions\Booking;

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
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob(int $scheduleTemplateId, array $data, string $timezone, string $currency)
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob(int $scheduleTemplateId, array $data, string $timezone, string $currency)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(int $scheduleTemplateId, array $data, string $timezone, string $currency)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean, int $scheduleTemplateId, array $data, string $timezone, string $currency)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean, int $scheduleTemplateId, array $data, string $timezone, string $currency)
 * @method static dispatchSync(int $scheduleTemplateId, array $data, string $timezone, string $currency)
 * @method static dispatchNow(int $scheduleTemplateId, array $data, string $timezone, string $currency)
 * @method static dispatchAfterResponse(int $scheduleTemplateId, array $data, string $timezone, string $currency)
 * @method static \App\Data\Booking\CreateBookingReturnData run(int $scheduleTemplateId, array $data, string $timezone, string $currency)
 */
class CreateBooking
{
}
/**
 * @method static \Lorisleiva\Actions\Decorators\JobDecorator|\Lorisleiva\Actions\Decorators\UniqueJobDecorator makeJob()
 * @method static \Lorisleiva\Actions\Decorators\UniqueJobDecorator makeUniqueJob()
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch()
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchIf(bool $boolean)
 * @method static \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent dispatchUnless(bool $boolean)
 * @method static dispatchSync()
 * @method static dispatchNow()
 * @method static dispatchAfterResponse()
 * @method static string run()
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
class SendConfirmationToVenueContacts
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