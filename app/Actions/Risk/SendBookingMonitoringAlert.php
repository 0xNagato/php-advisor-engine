<?php

namespace App\Actions\Risk;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Filament\Resources\RiskReviewResource\Pages\ViewRiskReview;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

class SendBookingMonitoringAlert
{
    use AsAction;

    /**
     * Send comprehensive booking monitoring alert to Slack
     * This sends ALL bookings (not just flagged ones) to a monitoring channel
     */
    public function handle(Booking $booking, array $riskResult): void
    {
        $webhookUrl = config('services.slack.all_bookings_webhook_url');

        if (! $webhookUrl) {
            return;
        }

        $message = $this->formatMessage($booking, $riskResult);

        try {
            $response = Http::post($webhookUrl, $message);

            if (! $response->successful()) {
                Log::error('Failed to send booking monitoring alert', [
                    'booking_id' => $booking->id,
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception sending booking monitoring alert', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format the comprehensive monitoring message
     */
    public function formatMessage(Booking $booking, array $riskResult): array
    {
        // Determine risk level and formatting
        $score = $booking->risk_score ?? 0;
        $isLowRisk = ! $booking->risk_state;
        $isMediumRisk = $booking->risk_state === 'soft';
        $isHighRisk = $booking->risk_state === 'hard';

        // Check if venue has platform integration
        $hasPlatformIntegration = $booking->venue->platforms()
            ->where('is_enabled', true)
            ->whereIn('platform_type', ['restoo', 'covermanager'])
            ->exists();

        // Check if this booking qualifies for auto-approval
        $qualifiesForAutoApproval = $isLowRisk &&
            $booking->guest_count <= 7 &&
            $hasPlatformIntegration;

        // Set emoji and color based on risk level
        if ($score === 0) {
            $riskEmoji = '⚪';
            $riskLabel = 'NO RISK';
            $color = '#e0e0e0';
        } elseif ($score < 10) {
            $riskEmoji = '🟢';
            $riskLabel = 'MINIMAL RISK';
            $color = 'good';
        } elseif ($score < 30) {
            $riskEmoji = '🟡';
            $riskLabel = 'LOW RISK';
            $color = '#90ee90';
        } elseif ($score < 70) {
            $riskEmoji = '🟠';
            $riskLabel = 'MEDIUM RISK';
            $color = 'warning';
        } else {
            $riskEmoji = '🔴';
            $riskLabel = 'HIGH RISK';
            $color = 'danger';
        }

        // Build URLs
        $bookingDetailUrl = ViewBooking::getUrl(['record' => $booking->id]);
        $riskReviewUrl = ViewRiskReview::getUrl(['record' => $booking->id]);

        $guestName = trim(($booking->guest_first_name ?? '').' '.($booking->guest_last_name ?? '')) ?: 'Unknown';

        // Build comprehensive fields with all available data
        $fields = [
            [
                'title' => 'Booking ID',
                'value' => '#'.$booking->id,
                'short' => true,
            ],
            [
                'title' => 'Risk Score',
                'value' => $score.'/100',
                'short' => true,
            ],
            [
                'title' => 'Guest',
                'value' => $guestName,
                'short' => true,
            ],
            [
                'title' => 'Email',
                'value' => $booking->guest_email ?? 'N/A',
                'short' => true,
            ],
            [
                'title' => 'Phone',
                'value' => $booking->guest_phone ?? 'N/A',
                'short' => true,
            ],
            [
                'title' => 'Venue',
                'value' => $booking->venue->name ?? 'Unknown',
                'short' => true,
            ],
            [
                'title' => 'Date/Time',
                'value' => $booking->booking_at?->format('M d, Y g:i A') ?? 'Unknown',
                'short' => true,
            ],
            [
                'title' => 'Party Size',
                'value' => $booking->guest_count.' guests',
                'short' => true,
            ],
            [
                'title' => 'Booking Type',
                'value' => $booking->is_prime ? '💎 Prime' : '🎟️ Non-Prime',
                'short' => true,
            ],
            [
                'title' => 'Total',
                'value' => $booking->is_prime ? money($booking->total_fee, $booking->currency) : 'N/A',
                'short' => true,
            ],
            [
                'title' => 'Status',
                'value' => $booking->status->label(),
                'short' => true,
            ],
            [
                'title' => 'Auto-Approval',
                'value' => $qualifiesForAutoApproval ? '✅ Yes' : '❌ No',
                'short' => true,
            ],
        ];

        // Add IP information if available
        if ($booking->ip_address) {
            $fields[] = [
                'title' => 'IP Address',
                'value' => $booking->ip_address,
                'short' => true,
            ];
        }

        // Add concierge info if applicable
        if ($booking->concierge_id && $booking->concierge_id != 1) {
            $fields[] = [
                'title' => 'Concierge',
                'value' => $booking->concierge->name ?? 'ID: '.$booking->concierge_id,
                'short' => true,
            ];
        }

        // Add device fingerprint if available
        if (! empty($riskResult['features']['device'])) {
            $fields[] = [
                'title' => 'Device ID',
                'value' => substr($riskResult['features']['device'], 0, 12).'...',
                'short' => true,
            ];
        }

        // Add risk breakdown with more detail
        if (! empty($riskResult['features']['breakdown'])) {
            $breakdown = $riskResult['features']['breakdown'];
            $breakdownText = [];
            foreach ($breakdown as $component => $componentScore) {
                if ($componentScore > 0) {
                    $breakdownText[] = ucfirst($component).': '.$componentScore;
                }
            }
            if (! empty($breakdownText)) {
                $fields[] = [
                    'title' => 'Risk Breakdown',
                    'value' => implode(', ', $breakdownText),
                    'short' => false,
                ];
            }
        }

        // Add all risk reasons if any exist
        if (! empty($riskResult['reasons']) && count($riskResult['reasons']) > 0) {
            $fields[] = [
                'title' => 'Risk Indicators',
                'value' => '• '.implode("\n• ", $riskResult['reasons']),
                'short' => false,
            ];
        }

        // Add velocity data if present
        if (! empty($riskResult['features']['velocity_count'])) {
            $fields[] = [
                'title' => 'IP Velocity',
                'value' => $riskResult['features']['velocity_count'].' bookings in last hour',
                'short' => true,
            ];
        }

        // Build footer with more context
        $footer = 'Prima Monitoring | '.($booking->venue->region ?? 'Unknown Region');
        if ($booking->created_at) {
            $footer .= ' | Created: '.$booking->created_at->format('H:i:s');
        }

        return [
            'channel' => '#ops-bookings-monitoring',
            'username' => 'Booking Monitor',
            'icon_emoji' => ':eyes:',
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $riskEmoji.' '.$riskLabel.' - Score: '.$score.'/100',
                    'title_link' => $bookingDetailUrl,
                    'text' => 'Complete booking details for monitoring and analysis',
                    'fields' => $fields,
                    'footer' => $footer,
                    'ts' => now()->timestamp,
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => '📋 View Booking',
                            'url' => $bookingDetailUrl,
                            'style' => 'primary',
                        ],
                        [
                            'type' => 'button',
                            'text' => '🔍 Risk Review',
                            'url' => $riskReviewUrl,
                            'style' => 'default',
                        ],
                    ],
                ],
            ],
        ];
    }
}
