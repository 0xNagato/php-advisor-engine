<?php

namespace App\Filament\Resources\ConciergeResource\Pages;

use App\Filament\DateRangeFilterAction;
use App\Filament\Resources\ConciergeResource;
use App\Filament\Resources\PartnerResource;
use App\Livewire\Concierge\ConciergeOverallLeaderboard;
use App\Livewire\Concierge\ConciergeRecentBookings;
use App\Livewire\ConciergeOverview;
use App\Models\Concierge;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

/**
 * @method Concierge getRecord()
 *
 * @property Concierge $record
 */
class ViewConcierge extends ViewRecord
{
    use HasFiltersAction;

    protected static string $view = 'filament.pages.concierge.concierge-dashboard';

    protected static string $resource = ConciergeResource::class;

    public function mount(int|string $record): void
    {
        $this->filters['startDate'] ??= now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] ??= now()->format('Y-m-d');

        parent::mount($record);
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->user->name;
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

        $concierge = $this->getRecord();
        $referrer = $concierge->user->referral->referrer ?? null;

        if ($referrer) {
            $referrerName = $referrer->name;
            $referrerType = $referrer->main_role;
            $referrerUrl = $this->getReferrerUrl($referrer);

            $subheading .= "<div class='mt-1 text-xs'>Referral: <a href='$referrerUrl' class='text-primary-600 hover:underline'>$referrerName</a> ($referrerType)</div>";
        }

        if ($concierge->hotel_name) {
            $subheading .= "<div class='mt-1 text-xs'>Hotel/Company: {$concierge->hotel_name}</div>";
        }

        return new HtmlString("<div class='flex flex-col'>$subheading</div>");
    }

    private function getReferrerUrl(User $referrer): string
    {
        if ($referrer->hasActiveRole('partner')) {
            return PartnerResource::getUrl('view', ['record' => $referrer->partner->id]);
        }

        if ($referrer->hasActiveRole('concierge')) {
            return ConciergeResource::getUrl('view', ['record' => $referrer->concierge->id]);
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
            ConciergeOverview::make([
                'concierge' => $this->getRecord(),
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            ConciergeRecentBookings::make([
                'concierge' => $this->getRecord(),
                'hideConcierge' => true,
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            ConciergeOverallLeaderboard::make([
                'concierge' => $this->getRecord(),
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
                'columnSpan' => '1',
            ]),
        ];
    }
}
