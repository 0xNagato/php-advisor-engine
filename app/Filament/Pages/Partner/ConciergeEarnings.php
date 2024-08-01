<?php

namespace App\Filament\Pages\Partner;

use App\Livewire\Partner\ConciergeReferralBookingsTable;
use App\Livewire\Partner\ConciergeReferralStats;
use App\Models\Concierge;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ConciergeEarnings extends Page
{
    use HasFiltersAction;

    public static ?string $title = 'Concierge Earnings';

    protected static ?string $navigationIcon = 'heroicon-s-currency-dollar';

    protected static string $view = 'filament.pages.concierge.concierge-referral-earnings';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'partner/concierge/earnings/{conciergeId?}';

    public ?int $conciergeId = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('partner');
    }

    public function getHeading(): string|Htmlable
    {
        if ($this->conciergeId) {
            $concierge = Concierge::query()->find($this->conciergeId);

            return "{$concierge->user->name} Bookings";
        }

        return 'My Concierge Earnings';
    }

    public function mount(?int $conciergeId = null): void
    {
        $this->conciergeId = $conciergeId;

        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        $concierge = new Concierge;
        if ($this->conciergeId) {
            $concierge = Concierge::query()->find($this->conciergeId);
        }

        return [
            ConciergeReferralStats::make([
                'concierge' => $concierge,
                'columnSpan' => 'full',
            ]),
            ConciergeReferralBookingsTable::make([
                'concierge' => $concierge,
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
