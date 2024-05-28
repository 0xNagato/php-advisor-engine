<?php

namespace App\Http\Integrations\Twilio\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class SendMessage extends Request implements HasBody
{
    use HasFormBody;

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::POST;

    public function __construct(
        public string $phone,
        public string $text,
        public ?string $from = null,
    ) {
    }

    protected function defaultBody(): array
    {
        return [
            'To' => $this->phone,
            'From' => $this->from ?? config('services.twilio.from'),
            'Body' => $this->text,
        ];
    }

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/Messages.json';
    }
}
