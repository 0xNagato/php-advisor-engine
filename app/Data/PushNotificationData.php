<?php

namespace App\Data;

use InvalidArgumentException;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Illuminate\Validation\Rules\MaxDigits;

class PushNotificationData extends Data
{
    public const TITLE_MAX_LENGTH = 512;

    public const BODY_MAX_LENGTH = 2048;

    public const TOTAL_PAYLOAD_MAX_SIZE = 4096;

    public function __construct(
        #[Max(self::TITLE_MAX_LENGTH)]
        #[StringType]
        public string $title,

        #[Max(self::BODY_MAX_LENGTH)]
        #[StringType]
        public string $body,

        public ?array $data = [],
    ) {
        $payloadSize = strlen($this->title) + strlen($this->body) + strlen(json_encode($this->data));
        throw_if($payloadSize > self::TOTAL_PAYLOAD_MAX_SIZE,
            new InvalidArgumentException('Total push notification payload cannot exceed '.self::TOTAL_PAYLOAD_MAX_SIZE.' bytes')
        );
    }
}
