<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\DateRangeFilterAction;
use App\Filament\Resources\PartnerResource;
use App\Livewire\Partner\PartnerOverallLeaderboard;
use App\Livewire\Partner\PartnerRecentBookings;
use App\Livewire\Partner\TopConcierges;
use App\Livewire\Partner\TopVenues;
use App\Livewire\PartnerOverview;
use App\Models\Partner;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Pages\Dashboard\Concerns\HasFilters;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

/**
 * @method Partner getRecord()
 *
 * @property Partner $record
 */
class ViewPartner extends ViewRecord
{
    use HasFilters;

    protected static string $resource = PartnerResource::class;

    protected static string $view = 'filament.pages.partner.partner-dashboard';

    public function mount(int|string $record): void
    {
        $this->filters['startDate'] ??= now()->subDays(30)->startOfDay()->format('Y-m-d');
        $this->filters['endDate'] ??= now()->endOfDay()->format('Y-m-d');

        parent::mount($record);
    }

    public function getHeading(): string|Htmlable
    {
        return $this->record->user->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! isset($this->filters['startDate'], $this->filters['endDate'])) {
            return null;
        }

        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);

        $formattedStartDate = $startDate->format('M j');
        $formattedEndDate = $endDate->format('M j');

        return $formattedStartDate.' - '.$formattedEndDate;
    }

    public function getHeaderWidgets(): array
    {
        return [
            PartnerOverview::make([
                'partner' => $this->record,
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            PartnerRecentBookings::make([
                'partner' => $this->record,
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            PartnerOverallLeaderboard::make([
                'partner' => $this->record,
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            TopConcierges::make([
                'partner' => $this->record,
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            TopVenues::make([
                'partner' => $this->record,
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->iconButton()
                ->redirectTo(config('app.platform_url'))
                ->hidden(fn () => isPrimaApp())
                ->record($this->getRecord()->user),
            EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->iconButton(),
            DateRangeFilterAction::make(),
        ];
    }
}
