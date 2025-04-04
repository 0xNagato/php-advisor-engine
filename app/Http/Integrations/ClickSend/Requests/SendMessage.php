<?php

namespace App\Http\Integrations\ClickSend\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class SendMessage extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $phone,
        protected string $text,
        protected ?string $from = null
    ) {
        $this->from = $from ?? config('services.clicksend.from');
    }

    public function resolveEndpoint(): string
    {
        return '/sms/send';
    }

    protected function defaultBody(): array
    {
        $message = [
            'to' => $this->phone,
            'body' => $this->text,
        ];

        if ($this->from) {
            $message['from'] = $this->from;
        }

        // ClickSend expects messages within a 'messages' array
        return [
            'messages' => [
                $message,
            ],
        ];
    }
}
