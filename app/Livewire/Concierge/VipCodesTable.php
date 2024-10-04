<?php

namespace App\Livewire\Concierge;

use App\Models\VipCode;
use App\Services\CurrencyConversionService;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\HasFilters;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VipCodesTable extends TableWidget
{
    use HasFilters;

    public static ?string $heading = 'VIP Codes';

    protected static bool $isLazy = true;

    public int|string|array $columnSpan;

    protected $listeners = ['concierge-referred' => '$refresh'];

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        $currencyService = app(CurrencyConversionService::class);
        $startDate = $this->tableFilters['startDate'];
        $endDate = $this->tableFilters['endDate'];

        $query = VipCode::with('concierge.user')
            ->with('earnings.booking:id,currency')
            ->withCount([
                'bookings' => function (Builder $query) {
                    $query->confirmed();
                },
            ])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        if (auth()->user()->hasRole('concierge')) {
            $query->where('concierge_id', auth()->user()->concierge->id);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('concierge.user.name')
                    ->label('User')->visible(fn () => auth()->user()->hasRole('super_admin')),
                TextColumn::make('code')
                    ->label('Code')
                    ->copyable()
                    ->copyMessage('Code copied')
                    ->copyableState(function (VipCode $vipCode) {
                        return $vipCode->link;
                    })
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-m-clipboard-document-check')
                    ->iconColor('primary')
                    ->iconPosition(IconPosition::After)
                    ->sortable(),
                TextColumn::make('bookings_count')
                    ->label('Bookings')->alignCenter()
                    ->sortable(),
                TextColumn::make('earnings')
                    ->default('0')
                    ->alignRight()
                    ->label('Earned')
                    ->formatStateUsing(function (VipCode $vipCode) use ($currencyService): string {
                        $earnings = $vipCode->earnings
                            ->where('user_id', $vipCode->concierge->user_id)
                            ->pluck('amount', 'booking.currency')->toArray();
                        $total = $currencyService->convertToUSD($earnings);

                        return money(round($total * 100), 'USD');
                    }),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->defaultSortOptionLabel('Created')
            ->defaultSort('created_at', 'desc');
    }
}
