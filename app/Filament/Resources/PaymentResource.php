<?php

namespace App\Filament\Resources;

use App\Enums\EarningType;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers\ItemsRelationManager;
use App\Models\Earning;
use App\Models\Payment;
use App\Models\Region;
use Carbon\Carbon;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('filter')
                    ->label('Filter')
                    ->schema([
                        Select::make('type')
                            ->label('Type')
                            ->options(EarningType::class)
                            ->live()
                            ->required(),
                        Select::make('month')
                            ->options(
                                collect(range(1, 12))
                                    ->mapWithKeys(fn ($month, $index) => [
                                        $index + 1 => date('F', mktime(0, 0, 0, $month, 1)),
                                    ])
                            )
                            ->live()
                            ->required(),
                        Select::make('currency')
                            ->options(
                                Region::all()->pluck('currency')->unique()->mapWithKeys(fn ($currency) => [$currency => $currency])
                            )
                            ->live()
                            ->label('Currency')
                            ->required(),
                    ])
                    ->columns(3)
                    ->afterStateUpdated(function (Get $get, $set) {
                        if (! $get('type') || ! $get('currency') || ! $get('month')) {
                            return null;
                        }

                        $total = Earning::query()
                            ->whereNull('payment_id')
                            ->where('type', $get('type'))
                            ->where('currency', $get('currency'))
                            ->whereMonth('created_at', (int) $get('month'))
                            ->sum('amount');

                        $set('amount', $total / 100);
                    }),
                TextInput::make('amount')
                    ->label('Total')
                    ->readOnly()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->prefix(fn ($get) => $get('currency') ?? null),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('status')
                    ->label('Status')
                    ->badge(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Type')
                    ->searchable()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->formatStateUsing(fn (string $state): string => Carbon::parse($state)->format('F j, Y')),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (Payment $record) => $record->currency, divideBy: 100)
                    ->label('Total'),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(auth()->user()->hasRole('super_admin'))
                    ->action(fn (Payment $record) => $record->markAsPaid()),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            //            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
