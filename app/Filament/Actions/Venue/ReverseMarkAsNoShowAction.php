<?php

namespace App\Filament\Actions\Venue;

use App\Events\BookingReverseMarkedAsNoShow;
use App\Models\Booking;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

class ReverseMarkAsNoShowAction extends Action
{
    protected function setUp(): void
    {
        $this->requiresConfirmation()
            ->modalHeading('Reverse Mark as No-Show')
            ->modalDescription(fn () => new HtmlString(<<<'HTML'
                                <div class="space-y-4 text-left">
                                    <p>Made a mistake?  If the customer attended the reservation, you can un-mark it as a NO SHOW.  The concierge which booked this reservat-prime concierge incentive plan.</p>
                                    <p class="text-center font-semibold">Are you sure this is correct?</p>
                                </div>
                            HTML))
            ->color('danger')
            ->icon('mdi-ghost-off-outline')
            ->action(function (Booking $record) {
                BookingReverseMarkedAsNoShow::dispatch($record);

                Notification::make()
                    ->title('Booking marked as reversed.')
                    ->success()
                    ->send();
            })
            ->hidden(fn (Booking $record) => $record->is_prime || ! $record->no_show);
    }
}
