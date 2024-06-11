<?php

namespace App\Filament\Pages\SpecialRequest;

use App\Livewire\SpecialRequest\CreateSpecialRequestForm;
use Filament\Pages\Page;

class CreateSpecialRequest extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.special-request.create-special-request';

    protected static bool $shouldRegisterNavigation = false;

    protected function getHeaderWidgets(): array
    {
        return [
            CreateSpecialRequestForm::class,
        ];
    }
}
