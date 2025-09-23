<?php

namespace App\Filament\Resources\RiskReviewResource\Pages;

use App\Filament\Resources\RiskReviewResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRiskReviews extends ListRecords
{
    protected static string $resource = RiskReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'hard' => Tab::make('High Risk')
                ->modifyQueryUsing(fn (Builder $query) => $query->hardRiskHold())
                ->badgeColor('danger'),

            'soft' => Tab::make('Medium Risk')
                ->modifyQueryUsing(fn (Builder $query) => $query->softRiskHold())
                ->badgeColor('warning'),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),

            'yesterday' => Tab::make('Yesterday')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()->subDay())),
        ];
    }
}
