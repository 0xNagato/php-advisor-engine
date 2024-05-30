<?php

namespace App\Filament\Actions\Restaurant;

use App\Events\BookingMarkedAsNoShow;
use App\Models\Booking;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

class MarkAsNoShowAction extends Action
{
    protected function setUp(): void
    {
        $this->requiresConfirmation()
            ->modalHeading('Mark as No-Show')
            ->modalDescription(fn () => new HtmlString(<<<'HTML'
                                <div class="space-y-4 text-left">
                                    <p>By marking this reservation as a no-show, the concierge responsible for booking this reservation will have their booking bounty reversed and deducted from their account.</p>
                                    <p class="text-center font-semibold">Are you sure this is correct?</p>
                                </div>
                            HTML))
            ->color('danger')
            ->icon('mdi-ghost-outline')
            ->action(function (Booking $record) {
                BookingMarkedAsNoShow::dispatch($record);

                Notification::make()
                    ->title('Booking marked as no-show.')
                    ->success()
                    ->send();
            })
            ->hidden(fn (Booking $record) => $record->is_prime || $record->no_show);
    }
}
