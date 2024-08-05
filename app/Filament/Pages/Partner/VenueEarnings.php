<?php

namespace App\Filament\Pages\Partner;

use App\Livewire\Partner\VenueReferralBookingsTable;
use App\Livewire\Partner\VenueReferralStats;
use App\Models\Venue;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class VenueEarnings extends Page
{
    use HasFiltersAction;

    public static ?string $title = 'Venue Earnings';

    protected static ?string $navigationIcon = 'heroicon-s-currency-dollar';

    protected static string $view = 'filament.pages.concierge.concierge-referral-earnings';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'partner/venue/earnings/{venueId?}';

    public ?int $venueId = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('partner');
    }

    public function getHeading(): string|Htmlable
    {
        if ($this->venueId) {
            $venue = Venue::query()->find($this->venueId);

            return "$venue->name Bookings";
        }

        return 'My Venue Earnings';
    }

    public function mount(?int $venueId = null): void
    {
        $this->venueId = $venueId;

        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        $venue = new Venue;
        if ($this->venueId) {
            $venue = Venue::query()->find($this->venueId);
        }

        return [
            VenueReferralStats::make([
                'venue' => $venue,
                'columnSpan' => 'full',
            ]),
            VenueReferralBookingsTable::make([
                'venue' => $venue,
                'columnSpan' => 'full',
            ]),
        ];
    }

    public function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Date Range')
                ->iconButton()
                ->icon('heroicon-o-calendar')
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                    // ...
                ]),
        ];
    }
}
