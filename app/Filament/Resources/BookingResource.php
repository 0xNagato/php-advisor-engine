<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages\CreateBooking;
use App\Filament\Resources\BookingResource\Pages\EditBooking;
use App\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Booking;
use DB;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'gmdi-restaurant-menu-o';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // If user is a venue manager, only show bookings for their venues
        if ($user->hasActiveRole('venue_manager')) {
            $venueGroups = $user->managedVenueGroups;

            if ($venueGroups && $venueGroups->count() > 0) {
                $query->where(function ($subquery) use ($venueGroups, $user) {
                    foreach ($venueGroups as $venueGroup) {
                        $allowedVenueIds = $venueGroup->getAllowedVenueIds($user);

                        if (blank($allowedVenueIds)) {
                            // If no specific venues are set, show all bookings from venues in the group
                            $venueIds = $venueGroup->venues()->pluck('id')->toArray();

                            // Use join to check schedule_template.venue_id
                            $subquery->orWhereExists(function ($scheduleQuery) use ($venueIds) {
                                $scheduleQuery->select(DB::raw(1))
                                    ->from('schedule_templates')
                                    ->whereIn('schedule_templates.venue_id', $venueIds)
                                    ->whereColumn('schedule_templates.id', 'bookings.schedule_template_id');
                            });

                            // Also check meta->venue->id for bookings that might not have schedule_template relation
                            $subquery->orWhereIn(DB::raw("(meta->'venue'->>'id')::int"), $venueIds);
                        } else {
                            // Use join to check schedule_template.venue_id
                            $subquery->orWhereExists(function ($scheduleQuery) use ($allowedVenueIds) {
                                $scheduleQuery->select(DB::raw(1))
                                    ->from('schedule_templates')
                                    ->whereIn('schedule_templates.venue_id', $allowedVenueIds)
                                    ->whereColumn('schedule_templates.id', 'bookings.schedule_template_id');
                            });

                            // Also check meta->venue->id for bookings that might not have schedule_template relation
                            $subquery->orWhereRaw("(meta->'venue'->>'id')::int IN (".implode(',',
                                $allowedVenueIds).')');
                        }
                    }
                });
            } else {
                // No venue groups assigned, don't show any bookings
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (session()->exists('simpleMode')) {
            return ! session('simpleMode');
        }

        return auth()->user()->hasActiveRole(['super_admin', 'partner', 'concierge', 'venue_manager']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('concierge_id')
                    ->required()
                    ->numeric(),
                TextInput::make('guest_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('guest_email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('guest_phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                TextInput::make('guest_count')
                    ->required()
                    ->numeric(),
                TextInput::make('total_fee')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->maxLength(255)
                    ->default('USD'),
                TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('confirmed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('concierge.user.name')
                    ->label('Concierge')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schedule.start_time')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('guest_name')
                    ->searchable(),
                TextColumn::make('guest_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guest_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_fee')
                    ->money(fn ($record) => $record->currency, divideBy: 100)
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'view' => ViewBooking::route('/{record}'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}
