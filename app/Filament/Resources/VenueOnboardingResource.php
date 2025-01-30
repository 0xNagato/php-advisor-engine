<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\VenueOnboardingResource\Pages\ListVenueOnboardings;
use App\Filament\Resources\VenueOnboardingResource\Pages\ViewVenueOnboarding;
use App\Models\User;
use App\Models\VenueOnboarding;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class VenueOnboardingResource extends Resource
{
    protected static ?string $model = VenueOnboarding::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Venues';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Company Information')
                    ->schema([
                        Placeholder::make('company_name')
                            ->label('Company Name')
                            ->content(fn (VenueOnboarding $record): string => $record->company_name),
                        Placeholder::make('partner_name')
                            ->label('PRIMA Partner')
                            ->content(function (VenueOnboarding $record): string|HtmlString {
                                if (! $record->partnerUser?->partner) {
                                    return 'No Partner Assigned';
                                }

                                $url = route('filament.admin.resources.partners.view', ['record' => $record->partnerUser->partner->id]);

                                return new HtmlString(
                                    "<a href='{$url}' class='text-primary-600 hover:text-primary-500'>{$record->partnerUser->first_name} {$record->partnerUser->last_name}</a>"
                                );
                            }),
                        Placeholder::make('venue_count')
                            ->label('Number of Venues')
                            ->content(fn (VenueOnboarding $record): string => $record->venue_count),
                        Placeholder::make('has_logos')
                            ->label('Has Logos')
                            ->content(fn (VenueOnboarding $record): string => $record->has_logos ? 'Yes' : 'No'),
                    ])->columns(2),

                Section::make('Contact Information')
                    ->schema([
                        Placeholder::make('contact_name')
                            ->label('Contact Name')
                            ->content(fn (VenueOnboarding $record): string => "{$record->first_name} {$record->last_name}"),
                        Placeholder::make('email')
                            ->label('Email')
                            ->content(fn (VenueOnboarding $record): string => $record->email),
                        Placeholder::make('phone')
                            ->label('Phone')
                            ->content(fn (VenueOnboarding $record): string => $record->phone),
                    ])->columns(3),

                Section::make('Venues')
                    ->schema([
                        Placeholder::make('venues')
                            ->hiddenLabel()
                            ->content(fn (VenueOnboarding $record) => new HtmlString(
                                view('components.venue-onboarding-locations', [
                                    'locations' => $record->locations,
                                ])->render()
                            )),
                    ]),

                Section::make('Processing')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('processed_by')
                                    ->label('Processed By')
                                    ->content(function (VenueOnboarding $record): string|HtmlString {
                                        if (! $record->processedBy) {
                                            return 'Not processed';
                                        }

                                        $url = EditUser::getUrl(['record' => $record->processedBy]);

                                        return new HtmlString(
                                            "<a href='{$url}' class='text-primary-600 hover:text-primary-500'>{$record->processedBy->name}</a>"
                                        );
                                    }),
                                Placeholder::make('processed_at')
                                    ->label('Processed At')
                                    ->content(fn (VenueOnboarding $record): string => $record->processed_at?->format('M j Y, g:ia') ?? 'Not processed'),
                            ]),
                        Placeholder::make('notes')
                            ->label('Processing Notes')
                            ->content(fn (VenueOnboarding $record): string => $record->notes ?? 'No processing notes'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('venue_count')
                    ->label('Venues')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime('m/d/Y g:i A')
                    ->timezone(auth()->user()->timezone ?? config('app.timezone'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVenueOnboardings::route('/'),
            'view' => ViewVenueOnboarding::route('/{record}'),
        ];
    }
}
