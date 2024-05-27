<?php

namespace App\Livewire\Restaurant;

use App\Enums\SpecialRequestStatus;
use App\Events\SpecialRequestAccepted;
use App\Events\SpecialRequestRejected;
use App\Models\SpecialRequest;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RestaurantSpecialRequestConfirmation extends Page
{
    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.restaurant-special-request-confirmation';

    public SpecialRequest $specialRequest;

    public string $message = '';

    public function mount(string $token): void
    {
        $this->specialRequest = SpecialRequest::where('uuid', $token)->firstOrFail();
    }

    public function confirmRequest(): void
    {
        $this->specialRequest->update([
            'status' => SpecialRequestStatus::Accepted,
            'restaurant_message' => $this->message,
        ]);

        SpecialRequestAccepted::dispatch($this->specialRequest);

        Notification::make()
            ->title('Special Request Accepted')
            ->success()
            ->send();
    }

    public function denyRequest(): void
    {
        $this->specialRequest->update([
            'status' => SpecialRequestStatus::Rejected,
            'restaurant_message' => $this->message,
        ]);

        SpecialRequestRejected::dispatch($this->specialRequest);

        Notification::make()
            ->title('Special Request Rejected')
            ->success()
            ->send();
    }
}
