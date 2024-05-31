<?php

namespace App\Livewire\SpecialRequest;

use App\Filament\Pages\SpecialRequest\ViewSpecialRequest;
use App\Models\SpecialRequest;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class SpecialRequestListItem extends Widget
{
    protected static string $view = 'livewire.special-request.special-request-list-item';

    public SpecialRequest $specialRequest;

    public function viewSpecialRequest()
    {
        return redirect()->route(ViewSpecialRequest::getRouteName(), ['specialRequest' => $this->specialRequest]);
    }

    #[Computed]
    public function statusColors(): string
    {
        return match ($this->specialRequest->status->value) {
            'pending' => 'text-orange-500',
            'accepted' => 'text-green-500',
            'rejected' => 'text-gray-500',
            'awaiting_reply' => 'text-blue-500',
            'awaiting_spend' => 'text-purple-500',
            'completed' => 'text-green-500',
            default => 'text-gray-500',
        };
    }

    #[Computed]
    public function formattedStatus(): string
    {
        return match ($this->specialRequest->status->value) {
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'awaiting_reply' => 'Needs Reply',
            'awaiting_spend' => 'Needs Spend',
            'completed' => 'Completed',
            default => 'Cancelled',
        };
    }
}
