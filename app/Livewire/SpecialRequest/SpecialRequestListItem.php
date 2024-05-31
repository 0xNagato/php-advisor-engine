<?php

namespace App\Livewire\SpecialRequest;

use App\Models\SpecialRequest;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class SpecialRequestListItem extends Widget
{
    protected static string $view = 'livewire.special-request.special-request-list-item';

    public SpecialRequest $specialRequest;

    #[Computed]
    public function statusColors(): string
    {
        return match ($this->specialRequest->status->value) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'accepted' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'awaiting_reply' => 'bg-blue-100 text-blue-800',
            'awaiting_spend' => 'bg-purple-100 text-purple-800',
            'completed' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    #[Computed]
    public function formattedStatus(): string
    {
        return match ($this->specialRequest->status->value) {
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'awaiting_reply' => 'Awaiting Reply',
            'awaiting_spend' => 'Awaiting Spend',
            'completed' => 'Completed',
            default => 'Cancelled',
        };
    }
}
