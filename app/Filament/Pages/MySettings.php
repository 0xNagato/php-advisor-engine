<?php

namespace App\Filament\Pages;

use App\Livewire\ProfileSettings;
use Filament\Pages\Page;

class MySettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament.pages.my-settings';

    protected static ?int $navigationSort = 101;

    public function getHeaderWidgets(): array
    {
        return [
            ProfileSettings::make(),
        ];
    }
}
