<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConciergeResource\Pages\CreateConcierge;
use App\Filament\Resources\ConciergeResource\Pages\EditConcierge;
use App\Filament\Resources\ConciergeResource\Pages\ListConcierges;
use App\Filament\Resources\ConciergeResource\Pages\ViewConcierge;
use App\Models\Concierge;
use Filament\Resources\Resource;

class ConciergeResource extends Resource
{
    protected static ?string $model = Concierge::class;

    protected static ?string $navigationIcon = 'govicon-user-suit';

    public static function getPages(): array
    {
        return [
            'index' => ListConcierges::route('/'),
            'create' => CreateConcierge::route('/create'),
            'view' => ViewConcierge::route('/{record}'),
            'edit' => EditConcierge::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }
}
