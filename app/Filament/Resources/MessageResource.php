<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Resources\Resource;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = -5;

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?string $title = 'Announcements';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['concierge', 'partner', 'restaurant']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'view' => Pages\ViewMessage::route('/{record}'),
        ];
    }
}
