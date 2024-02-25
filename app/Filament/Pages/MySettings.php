<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MySettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament.pages.my-settings';

    protected static ?int $navigationSort = 101;

    public static function canAccess(): bool
    {
        return true;
    }
}
