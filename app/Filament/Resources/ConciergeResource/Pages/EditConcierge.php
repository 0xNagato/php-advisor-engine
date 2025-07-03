<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Constants\BookingPercentages;
use App\Filament\Resources\ConciergeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\EditRecord;

class EditConcierge extends EditRecord
{
    protected static string $resource = ConciergeResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Hotel Information')
                    ->icon('heroicon-m-building-office')
                    ->schema([
                        TextInput::make('hotel_name')
                            ->label('Hotel Name')
                            ->placeholder('Hotel Name')
                            ->required(),
                    ]),
                Section::make('QR Concierge Configuration')
                    ->icon('heroicon-m-qr-code')
                    ->description('Configure QR Concierge settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_qr_concierge')
                                    ->label('QR Concierge')
                                    ->helperText('Enable this to designate this concierge as a QR concierge with QR code capabilities')
                                    ->reactive(),
                                TextInput::make('revenue_percentage')
                                    ->label('Revenue Percentage')
                                    ->helperText('Percentage of revenue this QR concierge will receive (default: 50%)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(BookingPercentages::VIP_ACCESS_DEFAULT_PERCENTAGE)
                                    ->suffix('%')
                                    ->visible(fn (Get $get): bool => $get('is_qr_concierge'))
                                    ->required(fn (Get $get): bool => $get('is_qr_concierge')),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
