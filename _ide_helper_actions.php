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
 * @method static \App\Models\Booking run(int $scheduleTemplateId, array $data, string $timezone, string $currency)
 */
class CreateBooking
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