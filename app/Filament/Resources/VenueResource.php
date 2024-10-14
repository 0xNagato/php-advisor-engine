<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenueResource\Pages\CreateVenue;
use App\Filament\Resources\VenueResource\Pages\EditVenue;
use App\Filament\Resources\VenueResource\Pages\ListVenues;
use App\Filament\Resources\VenueResource\Pages\ViewVenue;
use App\Models\Venue;
use App\Traits\ImpersonatesOther;
use Filament\Resources\Resource;

class VenueResource extends Resource
{
    use ImpersonatesOther;

    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = -1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVenues::route('/'),
            'create' => CreateVenue::route('/create'),
            'view' => ViewVenue::route('/{record}'),
            'edit' => EditVenue::route('/{record}/edit'),
        ];
    }
}
