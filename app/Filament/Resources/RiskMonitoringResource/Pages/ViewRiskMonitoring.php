<?php

namespace App\Filament\Resources\RiskMonitoringResource\Pages;

use App\Actions\Risk\ApproveRiskReview;
use App\Actions\Risk\RejectRiskReview;
use App\Filament\Resources\BookingResource;
use App\Filament\Resources\RiskMonitoringResource;
use App\Models\RiskWhitelist;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewRiskMonitoring extends ViewRecord
{
    protected static string $resource = RiskMonitoringResource::class;

    public function getTitle(): string
    {
        return 'Review Booking';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Risk Assessment')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('risk_score')
                            ->label('Risk Score')
                            ->badge()
                            ->color(fn ($state) => match (true) {
                                $state >= 70 => 'danger',
                                $state >= 30 => 'warning',
                                default => 'success'
                            })
                            ->formatStateUsing(fn ($state) => $state.'/100')
                            ->size('lg'),

                        TextEntry::make('risk_state')
                            ->label('Risk Level')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'hard' => 'danger',
                                'soft' => 'warning',
                                default => 'success'
                            })
                            ->formatStateUsing(fn ($state) => strtoupper($state ?? 'LOW')),

                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime('M j, g:i A'),
                    ]),

                Section::make('Booking Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('guest_name')
                            ->label('Guest Name')
                            ->weight('bold'),

                        TextEntry::make('guest_email')
                            ->label('Email')
                            ->copyable(),

                        TextEntry::make('guest_phone')
                            ->label('Phone')
                            ->copyable(),

                        TextEntry::make('venue.name')
                            ->label('Venue'),

                        TextEntry::make('guest_count')
                            ->label('Party Size')
                            ->formatStateUsing(fn ($state) => $state.' guests'),

                        TextEntry::make('booking_at')
                            ->label('Booking Date/Time')
                            ->dateTime('M j, Y g:i A'),

                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->copyable()
                            ->placeholder('Not available'),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->placeholder('Not available')
                            ->columnSpanFull(),
                    ]),

                Section::make('Concierge Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('concierge.user.name')
                            ->label('Concierge Name')
                            ->weight('bold')
                            ->placeholder('No concierge'),

                        TextEntry::make('concierge.hotel_name')
                            ->label('Hotel/Company')
                            ->placeholder('Not specified'),

                        TextEntry::make('concierge.user.email')
                            ->label('Concierge Email')
                            ->copyable()
                            ->placeholder('No email'),

                        TextEntry::make('concierge.user.phone')
                            ->label('Concierge Phone')
                            ->copyable()
                            ->placeholder('No phone'),
                    ])
                    ->visible(fn ($record) => $record->concierge !== null),

                Section::make('Risk Indicators')
                    ->schema([
                        TextEntry::make('placeholder')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                // Get the raw risk_reasons from attributes
                                $reasons = $record->getAttributes()['risk_reasons'] ?? null;

                                if (! $reasons) {
                                    return 'No risk indicators';
                                }

                                // Decode JSON if it's a string
                                if (is_string($reasons)) {
                                    $reasons = json_decode($reasons, true);
                                }

                                if (! is_array($reasons) || empty($reasons)) {
                                    return 'No risk indicators';
                                }

                                return new HtmlString(
                                    '<ul class="list-disc list-inside space-y-1">'.
                                    implode('', array_map(fn ($reason) => '<li>'.e($reason).'</li>', $reasons)).
                                    '</ul>'
                                );
                            })
                            ->html(),
                    ]),

                Section::make('Risk Score Breakdown')
                    ->schema([
                        ViewEntry::make('risk_metadata')
                            ->view('filament.components.risk-score-breakdown'),
                    ])
                    ->visible(function ($record) {
                        // Handle both RiskMetadata object and JSON string
                        $metadata = $record?->risk_metadata;
                        if (! $metadata) {
                            return false;
                        }
                        if (is_string($metadata)) {
                            $decoded = json_decode($metadata, true);

                            return isset($decoded['breakdown']);
                        }

                        return $metadata->breakdown !== null;
                    }),

                Section::make('AI Analysis')
                    ->schema([
                        TextEntry::make('ai_used')
                            ->label('AI Screening Used')
                            ->badge()
                            ->getStateUsing(function ($record) {
                                // Handle both RiskMetadata object and JSON string
                                $metadata = $record->risk_metadata;
                                if (is_string($metadata)) {
                                    $decoded = json_decode($metadata, true);

                                    return $decoded['llmUsed'] ?? false;
                                }

                                return $metadata?->llmUsed ?? false;
                            })
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),

                        TextEntry::make('ai_response')
                            ->label('AI Response')
                            ->getStateUsing(function ($record) {
                                // Handle both RiskMetadata object and JSON string
                                $metadata = $record->risk_metadata;
                                if (is_string($metadata)) {
                                    $decoded = json_decode($metadata, true);

                                    return $decoded['llmResponse'] ?? null;
                                }

                                return $metadata?->llmResponse ?? null;
                            })
                            ->formatStateUsing(function ($state) {
                                if (! $state) {
                                    return 'No AI response available';
                                }

                                $response = json_decode($state, true);
                                if (! $response) {
                                    return $state;
                                }

                                return new HtmlString(
                                    '<div class="space-y-2">'.
                                    '<p><strong>Risk Score:</strong> '.($response['risk_score'] ?? 'N/A').'/100</p>'.
                                    '<p><strong>Confidence:</strong> '.($response['confidence'] ?? 'N/A').'</p>'.
                                    '<p><strong>AI Reasons:</strong></p>'.
                                    '<ul class="list-disc list-inside">'.
                                    implode('', array_map(fn ($reason) => '<li>'.e($reason).'</li>', $response['reasons'] ?? [])).
                                    '</ul>'.
                                    '<p><strong>Analysis:</strong> '.e($response['analysis'] ?? 'N/A').'</p>'.
                                    '</div>'
                                );
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(function ($record) {
                        // Handle both RiskMetadata object and JSON string
                        $metadata = $record?->risk_metadata;
                        if (! $metadata) {
                            return false;
                        }
                        if (is_string($metadata)) {
                            $decoded = json_decode($metadata, true);

                            return $decoded['llmUsed'] ?? false;
                        }

                        return $metadata->llmUsed ?? false;
                    })
                    ->collapsible(),

                Section::make('Customer Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->placeholder('No notes provided')
                            ->html(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => ! empty($record->notes)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $booking = $this->record;

        // Only show approve/reject actions for bookings with risk state (flagged)
        if (! $booking->risk_state) {
            return [
                Action::make('viewFullBooking')
                    ->label('View Full Booking')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn () => BookingResource::getUrl('view', ['record' => $booking]))
                    ->openUrlInNewTab(false),
            ];
        }

        return [
            Action::make('approve')
                ->label('Approve Booking')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Approve Booking?')
                ->modalDescription('This will remove the risk hold and send all notifications to the guest and venue.')
                ->action(function () {
                    ApproveRiskReview::run($this->record);
                    Notification::make()
                        ->title('Booking approved')
                        ->success()
                        ->send();

                    $this->redirect(RiskReviewResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->reviewed_at === null),

            Action::make('reject')
                ->label('Reject Booking')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    RejectRiskReview::run($this->record, $data['reason']);
                    Notification::make()
                        ->title('Booking rejected')
                        ->success()
                        ->send();

                    $this->redirect(RiskReviewResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->reviewed_at === null),

            Action::make('whitelist_email')
                ->label('Whitelist Domain')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->action(function () {
                    $domain = substr(strrchr($this->record->guest_email, '@'), 1);
                    if ($domain) {
                        RiskWhitelist::query()->create([
                            'type' => RiskWhitelist::TYPE_DOMAIN,
                            'value' => $domain,
                            'notes' => "Added from booking #{$this->record->id}",
                            'created_by' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title("Domain {$domain} whitelisted")
                            ->success()
                            ->send();
                    }
                }),

            Action::make('view_booking')
                ->label('View Full Booking')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => route('filament.admin.resources.bookings.view', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
