<?php

namespace App\Filament\Resources;

use App\Actions\Risk\ApproveRiskReview;
use App\Actions\Risk\RejectRiskReview;
use App\Filament\Resources\RiskReviewResource\Pages\ListRiskReviews;
use App\Filament\Resources\RiskReviewResource\Pages\ViewRiskReview;
use App\Models\Booking;
use App\Models\RiskBlacklist;
use App\Models\RiskWhitelist;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class RiskReviewResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Risk Review';

    protected static ?string $pluralLabel = 'Risk Reviews';

    protected static ?string $navigationGroup = 'Risk Management';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->pendingRiskReview()
            ->with(['venue', 'concierge.user', 'reviewedBy', 'riskAuditLogs']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $hardCount = static::getEloquentQuery()->hardRiskHold()->count();

        return $hardCount > 0 ? 'danger' : 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Booking Information')
                    ->schema([
                        TextInput::make('guest_name')
                            ->label('Guest Name')
                            ->disabled(),
                        TextInput::make('guest_email')
                            ->label('Email')
                            ->disabled(),
                        TextInput::make('guest_phone')
                            ->label('Phone')
                            ->disabled(),
                        TextInput::make('venue.name')
                            ->label('Venue')
                            ->disabled(),
                        DateTimePicker::make('booking_at')
                            ->label('Booking Date/Time')
                            ->disabled(),
                        TextInput::make('guest_count')
                            ->label('Party Size')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Risk Assessment')
                    ->schema([
                        TextInput::make('risk_score')
                            ->label('Risk Score')
                            ->disabled()
                            ->suffix('/100'),
                        TextInput::make('risk_state')
                            ->label('Risk Level')
                            ->disabled(),
                        Textarea::make('risk_reasons')
                            ->label('Risk Reasons')
                            ->rows(4)
                            ->disabled()
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state),
                        TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Risk Score Breakdown')
                    ->schema([
                        View::make('filament.components.risk-score-breakdown')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record?->risk_metadata?->breakdown !== null)
                    ->collapsed(),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Customer Notes')
                            ->rows(3)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable()
                    ->limit(15)
                    ->tooltip(fn ($state) => $state),

                TextColumn::make('guest_name')
                    ->label('Guest')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => new HtmlString(sprintf(
                        '<div class="text-sm">%s</div><div class="text-xs text-gray-500">%s</div>',
                        e($state ?: 'Unknown'),
                        e(substr($record->guest_email ?? '', 0, 25).(strlen($record->guest_email ?? '') > 25 ? '...' : ''))
                    ))
                    )
                    ->html(),

                TextColumn::make('guest_phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('guest_count')
                    ->label('Party')
                    ->formatStateUsing(fn ($state) => $state),

                TextColumn::make('booking_at')
                    ->label('Date/Time')
                    ->dateTime('M j, g:i A')
                    ->sortable(),

                BadgeColumn::make('risk_score')
                    ->label('Score')
                    ->colors([
                        'success' => fn ($state) => $state < 30,
                        'warning' => fn ($state) => $state >= 30 && $state < 70,
                        'danger' => fn ($state) => $state >= 70,
                    ])
                    ->formatStateUsing(fn ($state) => $state.'/100'),

                BadgeColumn::make('risk_state')
                    ->label('Risk')
                    ->colors([
                        'warning' => 'soft',
                        'danger' => 'hard',
                    ])
                    ->formatStateUsing(fn ($state) => strtoupper((string) $state)),

                TextColumn::make('risk_reasons')
                    ->label('Reasons')
                    ->formatStateUsing(fn ($state) => is_array($state)
                            ? implode(', ', array_slice($state, 0, 1)).(count($state) > 1 ? '...' : '')
                            : ($state ? substr((string) $state, 0, 40).'...' : '')
                    )
                    ->tooltip(fn ($state) => is_array($state) ? implode("\n", $state) : $state
                    )
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->filters([
                SelectFilter::make('risk_state')
                    ->label('Risk Level')
                    ->options([
                        'soft' => 'Soft Hold',
                        'hard' => 'Hard Hold',
                    ]),

                Filter::make('score_high')
                    ->label('High Score (70+)')
                    ->query(fn (Builder $query) => $query->where('risk_score', '>=', 70)),

                Filter::make('score_medium')
                    ->label('Medium Score (30-69)')
                    ->query(fn (Builder $query) => $query->whereBetween('risk_score', [30, 69])),

                Filter::make('created_today')
                    ->label('Today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Booking?')
                    ->modalDescription('This will remove the risk hold and send all notifications.')
                    ->action(function ($record) {
                        ApproveRiskReview::run($record);
                        Notification::make()
                            ->title('Booking approved')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        RejectRiskReview::run($record, $data['reason']);
                        Notification::make()
                            ->title('Booking rejected')
                            ->success()
                            ->send();
                    }),

                ActionGroup::make([
                    Action::make('whitelist_email')
                        ->label('Whitelist Email Domain')
                        ->icon('heroicon-o-shield-check')
                        ->action(function ($record) {
                            $domain = substr(strrchr((string) $record->guest_email, '@'), 1);
                            if ($domain) {
                                RiskWhitelist::query()->create([
                                    'type' => RiskWhitelist::TYPE_DOMAIN,
                                    'value' => $domain,
                                    'notes' => "Added from booking #{$record->id}",
                                    'created_by' => auth()->id(),
                                ]);
                                Notification::make()
                                    ->title("Domain {$domain} whitelisted")
                                    ->success()
                                    ->send();
                            }
                        }),

                    Action::make('whitelist_phone')
                        ->label('Whitelist Phone')
                        ->icon('heroicon-o-shield-check')
                        ->action(function ($record) {
                            RiskWhitelist::query()->create([
                                'type' => RiskWhitelist::TYPE_PHONE,
                                'value' => $record->guest_phone,
                                'notes' => "Added from booking #{$record->id}",
                                'created_by' => auth()->id(),
                            ]);
                            Notification::make()
                                ->title('Phone whitelisted')
                                ->success()
                                ->send();
                        }),

                    Action::make('blacklist_email')
                        ->label('Blacklist Email Domain')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('danger')
                        ->action(function ($record) {
                            $domain = substr(strrchr((string) $record->guest_email, '@'), 1);
                            if ($domain) {
                                RiskBlacklist::query()->create([
                                    'type' => RiskBlacklist::TYPE_DOMAIN,
                                    'value' => $domain,
                                    'reason' => "Suspicious activity from booking #{$record->id}",
                                    'created_by' => auth()->id(),
                                ]);
                                Notification::make()
                                    ->title("Domain {$domain} blacklisted")
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            ApproveRiskReview::run($record);
                        }
                        Notification::make()
                            ->title(count($records).' bookings approved')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiskReviews::route('/'),
            'view' => ViewRiskReview::route('/{record}'),
        ];
    }
}
