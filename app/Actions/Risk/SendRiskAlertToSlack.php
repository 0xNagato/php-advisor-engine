<?php

namespace App\Actions\Risk;

use App\Filament\Resources\RiskReviewResource\Pages\ViewRiskReview;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class SendRiskAlertToSlack
{
    use AsAction;

    /**
     * Send risk alert to Slack
     */
    public function handle(Booking $booking, array $riskResult): void
    {
        $webhookUrl = config('services.slack.risk_webhook_url');

        if (config('app.debug')) {
            Log::debug('Attempting to send Slack risk alert', [
                'booking_id' => $booking->id,
                'risk_score' => $booking->risk_score,
                'webhook_configured' => !empty($webhookUrl),
                'webhook_url_length' => strlen($webhookUrl ?? ''),
            ]);
        }

        if (!$webhookUrl) {
            Log::warning('Slack webhook URL not configured for risk alerts', [
                'booking_id' => $booking->id,
            ]);
            return;
        }

        $message = $this->formatMessage($booking, $riskResult);

        try {
            $response = Http::post($webhookUrl, $message);

            if (!$response->successful()) {
                Log::error('Failed to send Slack risk alert', [
                    'booking_id' => $booking->id,
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception sending Slack risk alert', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format the Slack message
     */
    protected function formatMessage(Booking $booking, array $riskResult): array
    {
        // Determine risk level and formatting
        $score = $booking->risk_score ?? 0;
        $isLowRisk = !$booking->risk_state;
        $isMediumRisk = $booking->risk_state === 'soft';
        $isHighRisk = $booking->risk_state === 'hard';

        // Check if venue has platform integration for auto-approval
        $hasPlatformIntegration = $booking->venue->platforms()
            ->where('is_enabled', true)
            ->whereIn('platform_type', ['restoo', 'covermanager'])
            ->exists();

        // Check if this booking qualifies for auto-approval
        $qualifiesForAutoApproval = $isLowRisk &&
            $booking->guest_count <= 7 &&
            $hasPlatformIntegration;

        // Set emoji and color based on risk level
        if ($isLowRisk) {
            $riskEmoji = '🟢';
            $riskLabel = 'LOW RISK';
            $color = 'good';
            if ($qualifiesForAutoApproval) {
                $text = 'New booking received - will be auto-approved after platform sync.';
            } else {
                $text = 'New booking received - venue contacts notified for approval.';
            }
        } elseif ($isMediumRisk) {
            $riskEmoji = '🟡';
            $riskLabel = 'MEDIUM RISK';
            $color = 'warning';
            $text = 'Booking placed on hold - requires immediate review.';
        } else {
            $riskEmoji = '🔴';
            $riskLabel = 'HIGH RISK';
            $color = 'danger';
            $text = 'Booking placed on hold - requires immediate review.';
        }

        // Build URLs using Filament's proper URL generation
        $bookingDetailUrl = ViewBooking::getUrl(['record' => $booking->id]);

        // For risk reviews, link directly to the risk review view page
        $riskReviewUrl = ViewRiskReview::getUrl(['record' => $booking->id]);

        // Use appropriate URL based on risk level
        $primaryUrl = ($isMediumRisk || $isHighRisk) ? $riskReviewUrl : $bookingDetailUrl;

        $guestName = trim(($booking->guest_first_name ?? '') . ' ' . ($booking->guest_last_name ?? '')) ?: 'Unknown';
        $fields = [
            [
                'title' => 'Guest',
                'value' => $guestName . "\n" . ($booking->guest_email ?? ''),
                'short' => true
            ],
            [
                'title' => 'Venue',
                'value' => $booking->venue->name ?? 'Unknown',
                'short' => true
            ],
            [
                'title' => 'Date/Time',
                'value' => $booking->booking_at?->format('M d, Y g:i A') ?? 'Unknown',
                'short' => true
            ],
            [
                'title' => 'Party Size',
                'value' => $booking->guest_count . ' guests',
                'short' => true
            ],
            [
                'title' => 'Booking Type',
                'value' => $booking->is_prime ? '💎 Prime (Paid)' : '🎟️ Non-Prime',
                'short' => true
            ],
            [
                'title' => 'Total',
                'value' => $booking->is_prime ? money($booking->total_fee, $booking->currency) : 'N/A',
                'short' => true
            ],
            [
                'title' => 'Risk Score',
                'value' => $score . '/100',
                'short' => true
            ],
            [
                'title' => 'Risk Level',
                'value' => $riskLabel,
                'short' => true
            ]
        ];

        // Add auto-approval status for low-risk bookings
        if ($isLowRisk) {
            $fields[] = [
                'title' => 'Auto-Approval',
                'value' => $qualifiesForAutoApproval ? '✅ Eligible (≤7 guests, platform integrated)' : '❌ Not eligible',
                'short' => true
            ];
            $fields[] = [
                'title' => 'Platform Integration',
                'value' => $hasPlatformIntegration ? '✅ Yes' : '❌ No',
                'short' => true
            ];
        }

        // Add risk reasons if any exist
        if (!empty($riskResult['reasons']) && count($riskResult['reasons']) > 0) {
            $fields[] = [
                'title' => 'Risk Indicators',
                'value' => '• ' . implode("\n• ", array_slice($riskResult['reasons'], 0, 5)),
                'short' => false
            ];
        }

        // Build actions based on risk level
        $actions = [];
        if ($isMediumRisk || $isHighRisk) {
            $actions[] = [
                'type' => 'button',
                'text' => '🔍 Review Booking',
                'url' => $riskReviewUrl,
                'style' => 'primary'
            ];
        }
        $actions[] = [
            'type' => 'button',
            'text' => '📋 View Details',
            'url' => $bookingDetailUrl,
            'style' => $isLowRisk ? 'primary' : 'default'
        ];

        return [
            'channel' => '#ops-bookings',
            'username' => 'Booking System',
            'icon_emoji' => ':shield:',
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $riskEmoji . ' ' . $riskLabel . ' - Booking #' . $booking->id,
                    'title_link' => $primaryUrl,
                    'text' => $text,
                    'fields' => $fields,
                    'footer' => 'Prima Booking System | ' . $booking->venue->region ?? 'Unknown Region',
                    'ts' => now()->timestamp,
                    'actions' => $actions
                ]
            ]
        ];
    }
}