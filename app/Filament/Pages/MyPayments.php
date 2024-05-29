<?php

namespace App\Filament\Pages;

use App\Models\PaymentItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class MyPayments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.my-payments';

    protected static ?string $title = 'My Payments';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('concierge') || auth()->user()?->hasRole('restaurant') || auth()->user()?->hasRole('partner');
    }

    public function table(Table $table): Table
    {
        $query = PaymentItem::query()
            ->where('user_id', auth()->id())
            ->with('payment');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('payment.status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->formatStateUsing(fn (string $state): string => Carbon::parse($state)->format('F j, Y')),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (PaymentItem $record) => $record->currency, divideBy: 100)
                    ->label('Total'),
            ]);
    }
}
