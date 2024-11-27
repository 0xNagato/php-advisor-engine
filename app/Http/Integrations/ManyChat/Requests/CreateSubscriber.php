<?php

namespace App\Http\Integrations\ManyChat\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateSubscriber extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $firstName,
        protected string $lastName,
        protected string $whatsappPhone,
        protected ?string $phone = null,
        protected ?string $email = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/fb/subscriber/createSubscriber';
    }

    protected function defaultBody(): array
    {
        $body = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'whatsapp_phone' => $this->whatsappPhone,
            'has_opt_in_sms' => true,
            'has_opt_in_email' => true,
            'consent_phrase' => 'I agree to receive marketing communications from PRIMA.',
        ];

        if ($this->phone) {
            $body['phone'] = $this->phone;
        }

        if ($this->email) {
            $body['email'] = $this->email;
        }

        return $body;
    }
}
