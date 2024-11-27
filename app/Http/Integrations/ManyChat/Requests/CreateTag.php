<?php

namespace App\Http\Integrations\ManyChat\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateTag extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $tagName,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/fb/page/createTag';
    }

    protected function defaultBody(): array
    {
        return [
            'name' => $this->tagName,
        ];
    }
}
