<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskMonitoringResource\Pages;
use App\Models\Booking;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class RiskMonitoringResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'Risk Monitoring';

    protected static ?string $pluralLabel = 'All Bookings Monitor';

    protected static ?string $navigationGroup = 'Risk Management';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasActiveRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        // Show ALL bookings with risk scores, no user filtering
        return parent::getEloquentQuery()
            ->whereNotNull('risk_score')
            ->where('risk_score', '>', 0)
            ->with(['venue', 'concierge']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('risk_score')
                    ->label('Risk Score')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 70 => 'danger',
                        $state >= 30 => 'warning',
                        $state > 0 => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state . '/100'),

                TextColumn::make('risk_state')
                    ->label('Risk State')
                    ->badge()
                    ->default('Low')
                    ->color(fn ($state) => match($state) {
                        'hard' => 'danger',
                        'soft' => 'warning',
                        null, 'Low' => 'success',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'Low'),

                TextColumn::make('guest_first_name')
                    ->label('Guest')
                    ->formatStateUsing(fn (?string $state, $record): string =>
                        trim(($record->guest_first_name ?? '') . ' ' . ($record->guest_last_name ?? '')) ?: 'Unknown'
                    )
                    ->searchable(['guest_first_name', 'guest_last_name'])
                    ->sortable(),

                TextColumn::make('guest_email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied'),

                TextColumn::make('guest_phone')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('booking_at')
                    ->label('Booking Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                TextColumn::make('guest_count')
                    ->label('Guests')
                    ->sortable(),

                TextColumn::make('is_prime')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Prime' : 'Non-Prime')
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('IP copied')
                    ->toggleable(),

                TextColumn::make('risk_reasons')
                    ->label('Risk Reasons')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state) || empty($state)) {
                            return '-';
                        }
                        $reasons = array_slice($state, 0, 2);
                        $html = '<ul class="text-xs">';
                        foreach ($reasons as $reason) {
                            $html .= '<li>• '.e($reason).'</li>';
                        }
                        if (count($state) > 2) {
                            $html .= '<li class="text-gray-500">+'.(count($state) - 2).' more</li>';
                        }
                        $html .= '</ul>';

                        return new HtmlString($html);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'confirmed' => 'success',
                        'review_pending' => 'warning',
                        'cancelled' => 'danger',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('risk_state')
                    ->label('Risk Level')
                    ->options([
                        'clear' => 'Clear (Score < 30)',
                        'soft' => 'Medium Risk (30-69)',
                        'hard' => 'High Risk (70+)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            'clear' => $query->where(function ($q) {
                                $q->whereNull('risk_state')
                                  ->orWhere('risk_score', '<', 30);
                            }),
                            'soft' => $query->where('risk_state', 'soft'),
                            'hard' => $query->where('risk_state', 'hard'),
                            default => $query,
                        };
                    }),

                Filter::make('created_today')
                    ->label('Today\'s Bookings')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No scored bookings found')
            ->emptyStateDescription('Bookings with risk scores will appear here.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRiskMonitoring::route('/'),
            'view' => Pages\ViewRiskMonitoring::route('/{record}'),
        ];
    }
}