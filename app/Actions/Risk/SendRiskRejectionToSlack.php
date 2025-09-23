<?php

namespace App\Actions\Risk;

use App\Models\Booking;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class SendRiskRejectionToSlack
{
    use AsAction;

    /**
     * Send risk rejection notification to Slack
     */
    public function handle(Booking $booking, string $reason): void
    {
        $webhookUrl = config('services.slack.risk_webhook_url');

        if (! $webhookUrl) {
            Log::warning('Slack webhook URL not configured for risk alerts');

            return;
        }

        $message = $this->formatMessage($booking, $reason);

        try {
            $response = Http::post($webhookUrl, $message);

            if (! $response->successful()) {
                Log::error('Failed to send Slack rejection notification', [
                    'booking_id' => $booking->id,
                    'response' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Exception sending Slack rejection notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format the Slack message
     */
    protected function formatMessage(Booking $booking, string $reason): array
    {
        return [
            'channel' => '#ops-bookings',
            'username' => 'Risk Screening',
            'icon_emoji' => ':no_entry_sign:',
            'attachments' => [
                [
                    'color' => 'danger',
                    'title' => '❌ Booking Rejected After Risk Review',
                    'text' => "Booking #{$booking->id} has been rejected.",
                    'fields' => [
                        [
                            'title' => 'Guest',
                            'value' => $booking->guest_name ?? 'Unknown',
                            'short' => true,
                        ],
                        [
                            'title' => 'Venue',
                            'value' => $booking->venue->name ?? 'Unknown',
                            'short' => true,
                        ],
                        [
                            'title' => 'Risk Score',
                            'value' => $booking->risk_score.'/100',
                            'short' => true,
                        ],
                        [
                            'title' => 'Reviewed By',
                            'value' => $booking->reviewedBy?->name ?? 'System',
                            'short' => true,
                        ],
                        [
                            'title' => 'Rejection Reason',
                            'value' => $reason,
                            'short' => false,
                        ],
                    ],
                    'footer' => 'Booking ID: '.$booking->id,
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }
}
