<?php

namespace App\Livewire\SpecialRequest;

use App\Filament\Pages\SpecialRequest\ViewSpecialRequest;
use App\Models\SpecialRequest;
use App\Traits\SpecialRequest\UseSpecialRequestFormatting;
use Filament\Widgets\Widget;

class SpecialRequestListItem extends Widget
{
    use UseSpecialRequestFormatting;

    protected static string $view = 'livewire.special-request.special-request-list-item';

    public SpecialRequest $specialRequest;

    public function viewSpecialRequest()
    {
        return redirect()->route(ViewSpecialRequest::getRouteName(), ['specialRequest' => $this->specialRequest]);
    }
}
