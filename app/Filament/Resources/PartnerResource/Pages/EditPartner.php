<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Partner Information')
                    ->icon('gmdi-business-center-o')
                    ->schema([
                        TextInput::make('percentage')
                            ->label('Percentage')
                            ->placeholder('Percentage')
                            ->default(10)
                            ->suffix('%')
                            ->numeric()
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
