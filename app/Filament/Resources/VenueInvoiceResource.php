<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\VenueInvoiceStatus;
use App\Filament\Resources\VenueInvoiceResource\Pages;
use App\Models\VenueInvoice;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VenueInvoiceResource extends Resource
{
    protected static ?string $model = VenueInvoice::class;

    protected static ?string $navigationIcon = 'phosphor-invoice-duotone';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['venue'])
            ->latest();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable()
                    ->size('xs'),
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable()
                    ->sortable()
                    ->size('xs'),
                TextColumn::make('start_date')
                    ->label('Date Range')
                    ->formatStateUsing(fn (VenueInvoice $record): string => "{$record->start_date->format('M j, Y')} - {$record->end_date->format('M j, Y')}")
                    ->size('xs'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency, 100)
                    ->sortable()
                    ->size('xs'),
                TextColumn::make('status')
                    ->badge()
                    ->size('xs')
                    ->color(fn (VenueInvoiceStatus $state): string => match ($state) {
                        VenueInvoiceStatus::DRAFT => 'gray',
                        VenueInvoiceStatus::SENT => 'info',
                        VenueInvoiceStatus::PAID => 'success',
                        VenueInvoiceStatus::VOID => 'danger',
                    }),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->size('xs'),
                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->size('xs')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->size('xs')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(VenueInvoiceStatus::class),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', VenueInvoiceStatus::SENT->value)
                        ->where('due_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenueInvoices::route('/'),
            'view' => Pages\ViewVenueInvoice::route('/{record}'),
        ];
    }
}
