<?php

namespace App\Services;

use App\Models\SmsResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\RateLimiter;
use JsonException;

class SimpleTextingAdapter
{
    protected Client $client;

    protected mixed $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.simple_texting.api_key');
        $this->client = new Client([
            'base_uri' => config('services.simple_texting.base_uri'),
            'timeout' => 5.0,
        ]);
    }

    /**
     * @throws GuzzleException|JsonException
     */
    public function getMessages($page = 0, $size = 50, $accountPhone = null, $since = null, $contactPhone = null)
    {
        $response = $this->client->request('GET', 'messages', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'query' => [
                'page' => $page,
                'size' => $size,
                'accountPhone' => $accountPhone ?? config('services.simple_texting.from_phone'),
                'since' => $since,
                'contactPhone' => $contactPhone,
            ],
        ]);

        $body = $response->getBody();
        $content = $body->getContents();

        return json_decode($content, false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws GuzzleException|JsonException
     */
    public function sendMessage(
        $contactPhone,
        $text,
        $accountPhone = null,
        $mode = 'AUTO',
        $subject = null,
        $fallbackText = null,
        $mediaItems = [],
    )
    {
        ds([
            'contactPhone' => $contactPhone,
            'text' => $text,
            'accountPhone' => $accountPhone ?? config('services.simple_texting.from_phone'),
            'mode' => $mode,
            'subject' => $subject,
            'fallbackText' => $fallbackText,
            'mediaItems' => $mediaItems,
        ]);
        if (RateLimiter::tooManyAttempts('sms', 5)) {
            info('Rate limited: ' . $contactPhone . ' - ' . $text);

            return null;
        }

        $response = $this->client->request('POST', 'messages', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'json' => [
                'contactPhone' => $contactPhone,
                'text' => $text,
                'accountPhone' => $accountPhone ?? config('services.simple_texting.from_phone'),
                'mode' => $mode,
                'subject' => $subject,
                'fallbackText' => $fallbackText,
                'mediaItems' => $mediaItems,
            ],
        ]);

        SmsResponse::create([
            'phone_number' => $contactPhone,
            'response' => $response->getBody()->getContents(),
            'message' => $text,
        ]);

        RateLimiter::hit('sms');

        return json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
    }
}
