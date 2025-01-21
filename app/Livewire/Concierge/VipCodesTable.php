<?php

namespace App\Livewire\Concierge;

use App\Enums\BookingStatus;
use App\Models\VipCode;
use App\Services\CurrencyConversionService;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\HasFilters;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class VipCodesTable extends TableWidget
{
    use HasFilters;

    public static ?string $heading = 'VIP Codes';

    protected static bool $isLazy = true;

    public int|string|array $columnSpan;

    protected $listeners = ['concierge-referred' => '$refresh'];

    const bool USE_SLIDE_OVER = false;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $startDate = $this->tableFilters['startDate'];
        $endDate = $this->tableFilters['endDate'];

        $query = VipCode::with([
            'concierge.user',
            'earnings' => function ($query) use ($startDate, $endDate) {
                $query->whereNotNull('earnings.confirmed_at')
                    ->whereBetween('earnings.created_at', [$startDate, $endDate])
                    ->get(['amount', 'earnings.currency']);
            },
        ])
            ->withCount([
                'bookings' => function (Builder $query) use ($startDate, $endDate) {
                    $query->whereIn('status', BookingStatus::REPORTING_STATUSES)
                        ->whereBetween('created_at', [$startDate, $endDate]);
                },
            ]);

        if (auth()->user()->hasActiveRole('concierge')) {
            $query->where('concierge_id', auth()->user()->concierge->id);
        }

        return $table
            ->query($query)
            ->paginated(false)
            ->columns([
                TextColumn::make('concierge.user.name')
                    ->size('xs')
                    ->label('User')->visible(fn () => auth()->user()->hasActiveRole('super_admin')),
                TextColumn::make('code')
                    ->label('Code')
                    ->copyable()
                    ->size('xs')
                    ->copyMessage('VIP Link copied to clipboard')
                    ->copyableState(fn (VipCode $vipCode) => $vipCode->link)
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-m-clipboard-document-check')
                    ->iconColor('primary')
                    ->iconPosition(IconPosition::After),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->visibleFrom('sm')
                    ->alignCenter()
                    ->size('xs'),
                TextColumn::make('earnings')
                    ->label('Earned')
                    ->size('xs')
                    ->alignRight()
                    ->formatStateUsing(function (VipCode $vipCode): string {
                        $byCurrency = $vipCode->earnings->groupBy('currency')
                            ->map(fn ($currencyGroup) => $currencyGroup->sum('amount') * 100)
                            ->toArray();
                        $currencyService = app(CurrencyConversionService::class);

                        $inUsd = $currencyService->convertToUSD($byCurrency);

                        return money($inUsd, 'USD');
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')->date('M jS, Y')
                    ->label('Created')
                    ->visibleFrom('sm')
                    ->size('xs'),
            ])
            ->actions([
                Action::make('viewVipBookings')
                    ->iconButton()
                    ->icon('tabler-maximize')
                    ->modalHeading('VIP Bookings')
                    ->modalContent(fn (VipCode $vipCode) => view(
                        'partials.vip-code-modal-view',
                        ['vipCode' => $vipCode]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->slideOver(self::USE_SLIDE_OVER)
                    ->size('xs'),
            ])
            ->recordUrl(null)
            ->recordAction(fn (): string => 'viewVipBookings')
            ->defaultSortOptionLabel('Created')
            ->defaultSort('created_at', 'desc');
    }
}
