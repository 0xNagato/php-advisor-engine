<?php

namespace App\Http\Integrations\ManyChat\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class FindSubscriberByCustomField extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $fieldId,
        protected string $fieldValue,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/fb/subscriber/findByCustomField';
    }

    protected function defaultQuery(): array
    {
        return [
            'field_id' => $this->fieldId,
            'field_value' => $this->fieldValue,
        ];
    }
}
