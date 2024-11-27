<?php

namespace App\Http\Integrations\ManyChat\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class FindSubscriberByPhone extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $phone,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/fb/subscriber/findBySystemField';
    }

    protected function defaultQuery(): array
    {
        return [
            'phone' => $this->phone,
        ];
    }
}
