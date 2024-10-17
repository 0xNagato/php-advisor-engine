<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Filament\DateRangeFilterAction;
use App\Filament\Resources\PartnerResource;
use App\Filament\Resources\VenueResource;
use App\Livewire\Venue\VenueOverallLeaderboard;
use App\Livewire\Venue\VenueRecentBookings;
use App\Livewire\VenueOverview;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

/**
 * @method Venue getRecord()
 *
 * @property Venue $record
 */
class ViewVenue extends ViewRecord
{
    use HasFiltersAction;

    protected static string $view = 'filament.pages.venue.venue-dashboard';

    protected static string $resource = VenueResource::class;

    public function mount(int|string $record): void
    {
        $this->filters['startDate'] ??= now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] ??= now()->format('Y-m-d');

        parent::mount($record);
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $subheading = '';

        if (isset($this->filters['startDate'], $this->filters['endDate'])) {
            $startDate = Carbon::parse($this->filters['startDate']);
            $endDate = Carbon::parse($this->filters['endDate']);

            $formattedStartDate = $startDate->format('M j');
            $formattedEndDate = $endDate->format('M j');

            $subheading .= "$formattedStartDate - $formattedEndDate";
        }

        $venue = $this->getRecord();
        $referrer = $venue->user->referral->referrer ?? null;

        if ($referrer) {
            $referrerName = $referrer->name;
            $referrerType = $referrer->main_role;
            $referrerUrl = $this->getReferrerUrl($referrer);

            $subheading .= "<div class='mt-1 text-xs'>Referred by: <a href='$referrerUrl' class='text-primary-600 hover:underline'>$referrerName</a></div>";
        }

        return new HtmlString("<div class='flex flex-col'>$subheading</div>");
    }

    private function getReferrerUrl(User $referrer): string
    {
        if ($referrer->hasRole('partner')) {
            return PartnerResource::getUrl('view', ['record' => $referrer->partner->id]);
        }

        return '#';
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

    protected function getHeaderWidgets(): array
    {
        return [
            VenueOverview::make([
                'venue' => $this->getRecord(),
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            VenueRecentBookings::make([
                'venue' => $this->getRecord(),
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
                'columnSpan' => '1',
            ]),
            VenueOverallLeaderboard::make([
                'venue' => $this->getRecord(),
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
        ];
    }
}
