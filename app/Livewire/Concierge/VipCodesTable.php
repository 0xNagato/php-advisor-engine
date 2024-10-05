<?php

namespace App\Livewire\Concierge;

use App\Models\VipCode;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\HasFilters;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
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
        $startDate = $this->tableFilters['startDate'];
        $endDate = $this->tableFilters['endDate'];

        $query = VipCode::with(['concierge.user', 'earnings'])
            ->withCount('bookings')
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
                    ->copyMessage('VIP Link copied to clipboard')
                    ->copyableState(fn (VipCode $vipCode) => $vipCode->link)
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-m-clipboard-document-check')
                    ->iconColor('primary')
                    ->iconPosition(IconPosition::After)
                    ->sortable(),
                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('total_earnings_in_u_s_d')
                    ->label('Earned')
                    ->alignRight()
                    ->formatStateUsing(fn (VipCode $vipCode): string => money($vipCode->total_earnings_in_u_s_d * 100, 'USD')),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->defaultSortOptionLabel('Created')
            ->defaultSort('created_at', 'desc');
    }
}
