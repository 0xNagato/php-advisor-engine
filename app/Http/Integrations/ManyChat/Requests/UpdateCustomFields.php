<?php

namespace App\Http\Integrations\ManyChat\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateCustomFields extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $subscriberId,
        protected array $customFields,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/fb/subscriber/setCustomFields';
    }

    protected function defaultBody(): array
    {
        $fields = [];
        foreach ($this->customFields as $name => $value) {
            $fields[] = [
                'field_name' => $name,
                'field_value' => $value,
            ];
        }

        return [
            'subscriber_id' => $this->subscriberId,
            'fields' => $fields,
        ];
    }
}
