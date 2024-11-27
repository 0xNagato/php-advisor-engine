<?php

namespace App\Http\Integrations\ManyChat\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class AddSubscriberTag extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $subscriberId,
        protected string $tagName,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/fb/subscriber/addTagByName';
    }

    protected function defaultBody(): array
    {
        return [
            'subscriber_id' => $this->subscriberId,
            'tag_name' => $this->tagName,
        ];
    }
}
