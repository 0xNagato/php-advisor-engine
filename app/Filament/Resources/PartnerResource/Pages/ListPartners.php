<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use App\Models\Partner;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListPartners extends ListRecords
{
    use HasFiltersAction;

    protected static string $resource = PartnerResource::class;

    protected static string $view = 'filament.pages.partner.partner-list';

    public function getHeading(): Htmlable|string
    {
        if (auth()->user()->hasRole('super_admin')) {
            return 'Partners';
        }

        return 'My Earnings';
    }

    public function mount(): void
    {
        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['startDate'] ?? now()->subDays(30);
        $endDate = $this->filters['endDate'] ?? now();

        $query = Partner::query()
            ->select('partners.id', 'users.id as user_id', DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"), DB::raw('COALESCE(SUM(amount), 0) as total_earned'), DB::raw('COALESCE(COUNT(case when earnings.type in ("partner_concierge", "partner_restaurant") then 1 else null end), 0) as bookings'))
            ->join('users', 'users.id', '=', 'partners.user_id')
            ->leftJoin('earnings', function ($join) use ($startDate, $endDate) {
                $join->on('earnings.user_id', '=', 'users.id')
                    ->whereBetween('earnings.created_at', [$startDate, $endDate]);
            })
            ->groupBy('partners.id', 'users.id')
            ->orderBy('total_earned', 'desc')
            ->limit(10);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('user.name')
                    ->numeric()
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('total_earned')
                    ->label('Earned')
                    ->alignRight()
                    ->currency('USD'),
                TextColumn::make('bookings')
                    ->label('Bookings')
                    ->alignRight()
                    ->numeric(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->getKey();
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
            Actions\CreateAction::make()->iconButton()->icon('heroicon-s-plus-circle'),
        ];
    }
}
