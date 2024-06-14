<?php

namespace App\Livewire\Restaurant;

use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;

class RestaurantBookingConfirmation extends Page
{
    public Booking $booking;

    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'livewire.restaurant-booking-confirmation';

    public function mount(string $token): void
    {
        $this->booking = Booking::with('restaurant')->where('uuid', $token)->firstOrFail();
        // if ($this->booking->restaurant_confirmed_at === null) {
        //     Log::info('Restaurant confirmed booking', [
        //         'restaurant_name' => $this->booking->restaurant->restaurant_name,
        //         'booking' => $this->booking->id,
        //     ]);
        //     $this->booking->update(['restaurant_confirmed_at' => now()]);
        // }
    }

    public function confirmBookingAction(): Action
    {
        return Action::make('confirmBooking')
            ->label('Confirm Booking')
            ->color(Color::Green)
            ->requiresConfirmation()
            ->action(fn () => $this->confirmBooking());
    }

    private function confirmBooking(): void
    {
        if ($this->booking->restaurant_confirmed_at === null) {
            Log::info('Restaurant confirmed booking', [
                'restaurant_name' => $this->booking->restaurant->restaurant_name,
                'booking' => $this->booking->id,
            ]);
            $this->booking->update(['restaurant_confirmed_at' => now()]);
        }

        Notification::make()
            ->title('Thank you for confirming the booking')
            ->success()
            ->send();
    }
}
