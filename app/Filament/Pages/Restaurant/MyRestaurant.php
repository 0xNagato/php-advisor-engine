<?php

namespace App\Filament\Pages\Restaurant;

use App\Livewire\Restaurant\ScheduleManager;
use Filament\Pages\Page;

class MyRestaurant extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.my-restaurant';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Edit Availability';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $subheading = 'Please edit restaurant availability here.';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('restaurant');
    }

    public function getHeaderWidgets(): array
    {
        return [
            ScheduleManager::make(),
        ];
    }
}
