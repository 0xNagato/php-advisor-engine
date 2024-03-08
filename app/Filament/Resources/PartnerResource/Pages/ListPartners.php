<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ListRecords;

class ListPartners extends ListRecords
{
    use HasFiltersAction;

    protected static string $resource = PartnerResource::class;

    // protected static string $view = 'filament.resources.concierges.pages.view-concierge';

    public function mount(): void
    {
        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    protected function getHeaderActions(): array
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
            Actions\CreateAction::make()
                ->icon('gmdi-business-center-o')
                ->iconButton(),
        ];
    }
}
