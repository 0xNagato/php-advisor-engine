<?php

namespace App\Filament\Resources\RiskMonitoringResource\Pages;

use App\Filament\Resources\RiskMonitoringResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRiskMonitoring extends ListRecords
{
    protected static string $resource = RiskMonitoringResource::class;

    protected static ?string $title = 'All Bookings Risk Monitor';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getFilteredTableQuery()->count()),

            'high_risk' => Tab::make('High Risk (70+)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('risk_score', '>=', 70))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->where('risk_score', '>=', 70)->count())
                ->badgeColor('danger'),

            'medium_risk' => Tab::make('Medium Risk (30-69)')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('risk_score', [30, 69]))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->whereBetween('risk_score', [30, 69])->count())
                ->badgeColor('warning'),

            'low_risk' => Tab::make('Low Risk (1-29)')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('risk_score', [1, 29]))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->whereBetween('risk_score', [1, 29])->count())
                ->badgeColor('success'),

            'today' => Tab::make("Today's Bookings")
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->whereDate('created_at', today())->count()),

            'high_velocity' => Tab::make('High Velocity')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereIn('ip_address', function ($subquery) {
                        $subquery->select('ip_address')
                            ->from('bookings')
                            ->where('created_at', '>=', now()->subHour())
                            ->groupBy('ip_address')
                            ->havingRaw('COUNT(*) > 3');
                    });
                })
                ->badgeColor('warning'),
        ];
    }
}