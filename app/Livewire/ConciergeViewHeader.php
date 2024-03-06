<?php

namespace App\Livewire;

use App\Models\Concierge;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ConciergeViewHeader extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'livewire.concierge-view-header';

    public ?Concierge $concierge;

    public function getHeaderWidgets(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-s-pencil')
                ->record($this->concierge)
                ->iconButton(),
            Impersonate::make()
                ->iconButton()
                ->record($this->concierge->user),
        ];
    }
}
