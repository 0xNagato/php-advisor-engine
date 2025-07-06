<?php

namespace App\Http\Integrations\Twilio\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class LookupPhoneNumber extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(
        public string $phoneNumber,
        public array $fields = [],
    ) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/PhoneNumbers/'.rawurlencode($this->phoneNumber);
    }

    /**
     * Add query parameters for additional data packages
     */
    protected function defaultQuery(): array
    {
        $query = [];

        if (filled($this->fields)) {
            $query['Fields'] = implode(',', $this->fields);
        }

        return $query;
    }
}
