<?php

namespace App\Livewire;

use App\Models\Concierge;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Widgets\Widget;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ConciergeViewHeader extends Widget
{
    protected static string $view = 'livewire.concierge-view-header';
    public ?Concierge $concierge;

    public function impersonateUser(): Impersonate
    {
        return Impersonate::make()
            ->iconButton()
            ->record($this->concierge->user);
    }

    public function editConcierge(): Action
    {
        return EditAction::make()
            ->icon('heroicon-s-pencil')
            ->iconButton();
    }

    public function getHeaderWidgets(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-s-pencil')
                ->iconButton(),
            Impersonate::make()
                ->iconButton()
                ->record($this->concierge->user)
        ];
    }
}
