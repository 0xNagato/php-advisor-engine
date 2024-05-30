<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\Resources\ConciergeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
