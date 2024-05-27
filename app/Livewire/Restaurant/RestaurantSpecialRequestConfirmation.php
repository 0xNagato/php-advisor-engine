<?php

namespace App\Livewire\Restaurant;

use App\Models\SpecialRequest;
use Filament\Pages\Page;

class RestaurantSpecialRequestConfirmation extends Page
{
    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.restaurant-special-request-confirmation';

    public SpecialRequest $specialRequest;

    public function mount(string $token): void
    {
        $this->specialRequest = SpecialRequest::where('uuid', $token)->firstOrFail();
    }

    public function confirm(): void
    {
        $this->specialRequest->update([
            'status' => 'confirmed',
        ]);
    }
}
