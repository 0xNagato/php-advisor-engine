<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use App\Filament\Exports\EarningExporter;
use App\Models\PaymentItem;
use Carbon\Carbon;
use Filament\Actions\Exports\Models\Export;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user')
            ->columns([
                Tables\Columns\TextColumn::make('user.first_name')
                    ->label('First Name'),
                Tables\Columns\TextColumn::make('user.last_name')
                    ->label('Last Name'),
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(fn (string $state): string => Carbon::parse($state)->format('F j, Y')),
                Tables\Columns\TextColumn::make('amount')
                    ->summarize(
                        Sum::make()->label('Total')->money(
                            fn (PaymentItem $record) => $record->currency, divideBy: 100
                        )
                    )
                    ->money(fn (PaymentItem $record) => $record->currency, divideBy: 100),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export')
                    ->color('success')
                    ->exporter(EarningExporter::class)
                    ->fileName(fn (Export $export): string => "earnings-{$export->getKey()}")
                    ->columnMapping(false)
                    ->chunkSize(500),

            ]);
    }
}
