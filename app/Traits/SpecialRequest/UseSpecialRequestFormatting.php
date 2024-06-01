<?php

namespace App\Traits\SpecialRequest;

use Livewire\Attributes\Computed;

/**
 * Trait UseSpecialRequestFormatting
 *
 * Provides computed properties for formatting special request statuses.
 */
trait UseSpecialRequestFormatting
{
    /**
     * Get the color classes for the status.
     */
    #[Computed]
    public function statusColor(): string
    {
        return match ($this->specialRequest->status->value) {
            'pending' => 'text-indigo-600 bg-indigo-100 border-indigo-500',
            'accepted' => 'text-green-600 bg-green-100 border-green-500',
            'rejected' => 'text-orange-600 bg-orange-100 border-orange-500',
            'awaiting_reply' => 'text-blue-600 bg-blue-100 border-blue-500',
            'awaiting_spend' => 'text-blue-600 bg-blue-100 border-blue-500',
            'completed' => 'text-green-600 bg-green-100 border-green-500',
            'cancelled' => 'text-orange-600 bg-orange-100 border-orange-500',
            default => 'text-orange-600 bg-orange-100 border-orange-500',
        };
    }

    /**
     * Get the formatted status label.
     */
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
            'cancelled' => 'Cancelled',
            default => 'Cancelled',
        };
    }

    /**
     * Get the border-top classes for the status.
     */
    #[Computed]
    public function borderTop(): string
    {
        return match ($this->specialRequest->status->value) {
            'pending' => 'border-t-8 border-indigo-500',
            'accepted' => 'border-t-8 border-green-500',
            'awaiting_spend' => 'border-t-8 border-blue-500',
            'awaiting_reply' => 'border-t-8 border-blue-500',
            'rejected' => 'border-t-8 border-orange-500',
            'completed' => 'border-t-8 border-green-500',
            'cancelled' => 'border-t-8 border-orange-500',
            default => 'border-t-8 border-orange-500',
        };
    }
}
