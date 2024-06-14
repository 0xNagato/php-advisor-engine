<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages\CreatePartner;
use App\Filament\Resources\PartnerResource\Pages\EditPartner;
use App\Filament\Resources\PartnerResource\Pages\ListPartners;
use App\Filament\Resources\PartnerResource\Pages\ViewPartner;
use App\Models\Partner;
use App\Traits\ImpersonatesOther;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    use ImpersonatesOther;

    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'gmdi-business-center-o';

    protected static ?int $navigationSort = -0;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\TextInput::make('user_id')
                //     ->required()
                //     ->numeric(),
                // Forms\Components\TextInput::make('percentage')
                //     ->required()
                //     ->maxLength(255)
                //     ->default(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return (new self())->configureTable($table);
    }

    public function configureTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->numeric()
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('total_earned')
                    ->label('Earned')
                    ->alignRight()
                    ->money('USD', divideBy: 100),
                TextColumn::make('bookings')
                    ->label('Bookings')
                    ->alignRight()
                    ->numeric(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('impersonate')
                    ->iconButton()
                    ->icon('impersonate-icon')
                    ->action(fn (Partner $record) => $this->impersonate($record->user)),
                EditAction::make()
                    ->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
            'create' => CreatePartner::route('/create'),
            'view' => ViewPartner::route('/{record}'),
            'edit' => EditPartner::route('/{record}/edit'),
        ];
    }
}
