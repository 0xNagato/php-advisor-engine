<?php

namespace App\Http\Integrations\SimpleTexting\Requests;

use App\Http\Integrations\SimpleTexting\SimpleTexting;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class SendMessage extends Request implements HasBody
{
    use HasJsonBody;

    protected string $connector = SimpleTexting::class;

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::POST;

    public function __construct(
        protected string $phone,
        protected string $text,
    ) {}

    protected function defaultBody(): array
    {
        return [
            'contactPhone' => $this->phone,
            'text' => $this->text,
            'accountPhone' => config('services.simple_texting.from_phone'),
            'mode' => 'AUTO',
        ];
    }

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/messages';
    }
}
