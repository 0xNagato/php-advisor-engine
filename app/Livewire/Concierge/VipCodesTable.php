<?php

namespace App\Livewire\Concierge;

use App\Actions\VipCode\GenerateVipReferralQRCode;
use App\Enums\BookingStatus;
use App\Models\VipCode;
use App\Services\CurrencyConversionService;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\HasFilters;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
                Action::make('viewQR')
                    ->iconButton()
                    ->icon('tabler-qrcode')
                    ->size('xs')
                    ->modalWidth(MaxWidth::ExtraSmall)
                    ->modalHeading('')
                    ->modalContent(function (VipCode $vipCode): HtmlString {
                        $qrCodeData = $this->getQr($vipCode);
                        $showPrintButton = config('app.features.show_qr_code_print_button', false);

                        $printButton = '';
                        if ($showPrintButton) {
                            $printUrl = route('vip-code.print', [
                                'code' => $vipCode->code,
                                'svg_path' => $qrCodeData['svgPath'],
                            ]);

                            $printButton = <<<HTML
                                <a href="{$printUrl}"
                                target="_blank"
                                class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-semibold text-white transition-colors bg-green-600 rounded-md shadow-sm hover:bg-green-700 sm:text-base">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print QR Code
                                </a>
                            HTML;
                        }

                        return new HtmlString(<<<HTML
                                    <div class="flex flex-col items-center">
                                      <img src="{$qrCodeData['image']}" alt="{$vipCode->code} QR Code" class="mb-4 w-85 h-85">
                                      <div class="flex flex-col w-full max-w-xs mt-2 space-y-3">
                                        <a href="{$qrCodeData['downloadURL']}" download="prima-referral-{$vipCode->code}.svg"
                                          class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-semibold text-white transition-colors bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-700 sm:text-base">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                          </svg>
                                          Download QR Code
                                        </a>
                                        {$printButton}
                                      </div>
                                    </div>
                                HTML
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
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

    private function getQr(VipCode $vipCode): array
    {
        return app(GenerateVipReferralQRCode::class)->execute($vipCode);
    }
}
