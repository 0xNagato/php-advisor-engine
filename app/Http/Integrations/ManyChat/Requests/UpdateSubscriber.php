<?php

namespace App\Http\Integrations\ManyChat\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateSubscriber extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $subscriberId,
        protected ?string $firstName = null,
        protected ?string $lastName = null,
        protected ?string $whatsappPhone = null,
        protected ?string $email = null,
        protected ?string $phone = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/fb/subscriber/updateSubscriber';
    }

    protected function defaultBody(): array
    {
        $body = [
            'subscriber_id' => $this->subscriberId,
            'has_opt_in_sms' => true,
            'has_opt_in_email' => true,
            'consent_phrase' => 'I agree to receive marketing communications from PRIMA.',
        ];

        if ($this->firstName !== null) {
            $body['first_name'] = $this->firstName;
        }

        if ($this->lastName !== null) {
            $body['last_name'] = $this->lastName;
        }

        if ($this->whatsappPhone !== null) {
            $body['whatsapp_phone'] = $this->whatsappPhone;
        }

        if ($this->phone !== null) {
            $body['phone'] = $this->phone;
        }

        if ($this->email !== null) {
            $body['email'] = $this->email;
        }

        return $body;
    }
}
