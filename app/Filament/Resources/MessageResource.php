<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages\ListMessages;
use App\Filament\Resources\MessageResource\Pages\ViewMessage;
use App\Models\Message;
use Filament\Resources\Resource;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = -5;

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?string $title = 'Announcements';

    public static function getNavigationBadge(): ?string
    {
        return auth()->user()->unread_message_count > 0 ? (string) auth()->user()->unread_message_count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function canAccess(): bool
    {
        if (session()->exists('simpleMode')) {
            return ! session('simpleMode');
        }

        return auth()->user()?->hasActiveRole(['concierge']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessages::route('/'),
            'view' => ViewMessage::route('/{record}'),
        ];
    }
}
