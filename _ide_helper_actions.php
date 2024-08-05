<?php

namespace App\Actions\Booking;

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
class CreateBooking {}
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
class SendConfirmationToVenueContacts {}

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
class InviteConciergeViaSms {}

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
class CreateSpecialRequest {}

namespace Lorisleiva\Actions\Concerns;

/**
 * @method void asController()
 */
trait AsController {}
/**
 * @method void asListener()
 */
trait AsListener {}
/**
 * @method void asJob()
 */
trait AsJob {}
/**
 * @method void asCommand(\Illuminate\Console\Command $command)
 */
trait AsCommand {}
