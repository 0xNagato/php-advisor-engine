<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Filament\Resources\RestaurantResource;
use App\Livewire\Restaurant\RestaurantLeaderboard;
use App\Livewire\Restaurant\RestaurantRecentBookings;
use App\Livewire\Restaurant\RestaurantStats;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ViewRestaurant extends ViewRecord
{
    protected static string $resource = RestaurantResource::class;

    protected static string $view = 'filament.resources.restaurants.pages.view-restaurant';

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->restaurant_name;
    }

    // public function getSubheading(): string|Htmlable|null
    // {
    //     return $this->getRecord()->user->name;
    // }

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->iconButton()
                ->record($this->getRecord()->user),
            Actions\EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->iconButton(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RestaurantStats::make(['restaurant' => $this->getRecord(), 'columnSpan' => 'full']),
            RestaurantRecentBookings::make(['restaurant' => $this->getRecord(), 'columnSpan' => '1']),
            RestaurantLeaderboard::make(['restaurant' => $this->getRecord(), 'columnSpan' => '1']),
        ];
    }
}
