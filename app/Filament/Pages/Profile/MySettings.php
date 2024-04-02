<?php

namespace App\Filament\Pages\Profile;

use App\Livewire\Profile\ProfileSettings;
use Filament\Pages\Page;

class MySettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament.pages.my-settings';

    protected static ?int $navigationSort = 101;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'My Profile';

    public function getHeaderWidgets(): array
    {
        return [
            ProfileSettings::make(),
        ];
    }
}
