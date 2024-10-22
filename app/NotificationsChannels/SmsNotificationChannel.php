<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */
/** @noinspection PhpUnreachableStatementInspection */

namespace App\NotificationsChannels;

use App\Constants\SmsTemplates;
use App\Data\SmsData;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Exception;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmsNotificationChannel
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function send($notifiable, Notification $notification): void
    {
        throw_unless(method_exists($notification, 'toSms'), new RuntimeException('The notification must have a toSms method'));
        throw_unless($notification->toSms($notifiable) instanceof SmsData, new Exception('toSms should return SmsData'));

        /** @var SmsData $data */
        $data = $notification->toSms($notifiable);

        if ($data->templateKey) {
            $template = SmsTemplate::query()->where('key', $data->templateKey)->first();

            $templateContent = $template->content ?? (SmsTemplates::TEMPLATES[$data->templateKey] ?? null);

            if ($templateContent) {
                $data->text = $this->parseTemplate($templateContent, $data->templateData);
            } else {
                Log::error("SMS template not found for key: $data->templateKey");
                throw new RuntimeException("SMS template not found for key: $data->templateKey");
            }
        }

        if (app()->isLocal()) {
            Log::info('Sending SMS to '.$data->phone, [
                'templateKey' => $data->templateKey,
                'text' => $data->text,
                'phone' => $data->phone,
                ...$data->templateData,
            ]);

            return;
        }

        $response = (new SmsService)->sendMessage(
            contactPhone: $data->phone,
            text: $data->text,
        );

        if ($response->failed()) {
            event(new NotificationFailed(
                $notifiable,
                $notification,
                'sms',
                [
                    'message' => $response->status(),
                    'exception' => $response->body(),
                ]
            ));
        }
    }

    private function parseTemplate(string $template, array $data): string
    {
        return preg_replace_callback('/\{(\w+)\}/', fn ($matches) => $data[$matches[1]] ?? $matches[0], $template);
    }
}
