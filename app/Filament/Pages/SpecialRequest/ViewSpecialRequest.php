<?php

namespace App\Filament\Pages\SpecialRequest;

use App\Models\SpecialRequest;
use Filament\Pages\Page;

class ViewSpecialRequest extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.special-request.view-special-request';

    protected static ?string $slug = '/special-requests/{specialRequest?}';

    public SpecialRequest $specialRequest;

    public function mount(SpecialRequest $specialRequest): void
    {
        $this->specialRequest = $specialRequest;
        $this->authorize('view', $specialRequest);
    }
}
