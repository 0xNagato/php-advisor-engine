<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'gmdi-restaurant-menu-o';

    public static function shouldRegisterNavigation(): bool
    {
        if (session()->exists('simpleMode')) {
            return ! session('simpleMode');
        }

        return auth()->user()->hasRole(['concierge', 'super_admin', 'partner']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('concierge_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('guest_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('guest_email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('guest_phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('guest_count')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_fee')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(255)
                    ->default('USD'),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('confirmed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('concierge.user.name')
                    ->label('Concierge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('schedule.restaurant.restaurant_name')
                    ->label('Restaurant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('schedule.start_time')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guest_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guest_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('guest_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('guest_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_fee')
                    ->currency('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->actions([

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
