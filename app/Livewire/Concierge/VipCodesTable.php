<?php

namespace App\Livewire\Concierge;

use App\Enums\BookingStatus;
use App\Models\VipCode;
use App\Services\CurrencyConversionService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
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

                        return new HtmlString(<<<HTML
                                    <div class="flex flex-col items-center">
                                      <img src="{$qrCodeData['image']}" alt="{$vipCode->code} QR Code" class="w-85 h-85">
                                      <a href="{$qrCodeData['downloadURL']}" download="prima-referral-{$vipCode->code}.svg"
                                        class="text-sm text-indigo-600 underline hover:text-indigo-800">
                                        Download QR Code (SVG)
                                      </a>
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
        $qrLinkData = [
            'code' => $vipCode->code,
        ];

        if (isset($vipCode->concierge_id)) {
            $qrLinkData['cid'] = $vipCode->concierge_id;
        }

        $qrLink = config('app.primary_domain').'/';
        $qrLink .= ltrim(route('v.booking', $qrLinkData, false), '/');

        // Generate QR code for display (PNG - unchanged)
        $qrDisplayOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => 5,
            'outputBase64' => true, // Default, but explicit for clarity
        ]);
        $qrCodeImage = (new QRCode($qrDisplayOptions))->render($qrLink); // This is already a data URI

        // --- Downloadable SVG with Customizations ---
        $logoText = 'PRIMA';
        $ribbonText = 'Book And Go.';
        $fontFamily = 'Inter, Arial, sans-serif'; // Prioritize Inter, fallback to Arial/sans-serif

        $qrCustomSvgOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => 10,
            'addLogoSpace' => true,
            'logoSpaceWidth' => 17, // Increased from 15 for a bit more PRIMA padding
            'logoSpaceHeight' => 11, // Increased from 10 for a bit more PRIMA padding
            'outputBase64' => false,
            'svgAddXmlHeader' => true,
        ]);

        $rawSvgQrCode = (new QRCode($qrCustomSvgOptions))->render($qrLink);

        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (@$dom->loadXML($rawSvgQrCode)) {
            $svgRoot = $dom->documentElement;

            $viewBoxValues = explode(' ', $svgRoot->getAttribute('viewBox'));
            $vbX = (float) ($viewBoxValues[0] ?? 0);
            $vbY = (float) ($viewBoxValues[1] ?? 0);
            $vbWidth = (float) ($viewBoxValues[2] ?? 200);
            $vbHeight = (float) ($viewBoxValues[3] ?? 200);

            $textPrima = $dom->createElement('text', $logoText);
            $textPrima->setAttribute('x', (string) ($vbWidth / 2));
            $textPrima->setAttribute('y', (string) ($vbHeight / 2));
            $textPrima->setAttribute('font-family', $fontFamily);
            $textPrima->setAttribute('font-weight', 'bold'); // Make PRIMA bold
            // Reduce font size multiplier for PRIMA to give it more padding
            $primaFontSize = $vbHeight * ($qrCustomSvgOptions->logoSpaceHeight / 100) * 0.5;
            $textPrima->setAttribute('font-size', (string) max(5, $primaFontSize));
            $textPrima->setAttribute('text-anchor', 'middle');
            $textPrima->setAttribute('dominant-baseline', 'central');
            $textPrima->setAttribute('fill', 'black');
            $svgRoot->appendChild($textPrima);

            $ribbonStripHeight = $vbHeight * 0.18;
            $ribbonTextSize = $vbHeight * 0.1; // Target font size around 3.0-3.7 for typical QR sizes
            $overlapAmount = $vbHeight * 0.04;   // Keep overlap

            // Y coordinates for the entire ribbon assembly
            $ribbonTopY = $vbHeight - $overlapAmount;
            $ribbonMidY = $ribbonTopY + ($ribbonStripHeight / 2);
            $ribbonBottomY = $ribbonTopY + $ribbonStripHeight;

            // Parameters for tails
            $foldWidth = $ribbonStripHeight * 0.7;
            $vNotchDepth = $foldWidth * 0.6; // How deep the V-notch cuts into the foldWidth
            $tailVerticalOffset = $ribbonStripHeight * 0.12; // How much tails are shifted upwards

            // Y-coordinates for the TAILS (shifted upwards)
            $tailTopY = $ribbonTopY - $tailVerticalOffset;
            $tailMidY = $ribbonMidY - $tailVerticalOffset;
            $tailBottomY = $ribbonBottomY - $tailVerticalOffset;

            // X Coordinates for central rectangle (main body of ribbon)
            $mainRectLeftX = $vbX;
            $mainRectRightX = $vbX + $vbWidth;

            // X Coordinates for left tail
            $leftTailOuterX = $mainRectLeftX - $foldWidth;
            $leftTailVCutX = $leftTailOuterX + $vNotchDepth;

            // X Coordinates for right tail
            $rightTailOuterX = $mainRectRightX + $foldWidth;
            $rightTailVCutX = $rightTailOuterX - $vNotchDepth;

            // Adjust overall SVG viewBox and physical dimensions for the entire assembly
            $totalSvgWidth = $vbWidth + (2 * $foldWidth);
            $viewBoxOriginX = $vbX - $foldWidth;
            // ViewBox Y must now start at the top of the tails if they are higher
            $viewBoxOriginY = min($vbY, $tailTopY);
            // ViewBox height must encompass from its origin down to the bottom of the central ribbon body
            $viewBoxHeight = $ribbonBottomY - $viewBoxOriginY;

            $svgRoot->setAttribute('width', (string) ($totalSvgWidth * $qrCustomSvgOptions->scale));
            $svgRoot->setAttribute('height', (string) ($viewBoxHeight * $qrCustomSvgOptions->scale));
            $svgRoot->setAttribute('viewBox', sprintf('%f %f %f %f',
                $viewBoxOriginX, $viewBoxOriginY, $totalSvgWidth, $viewBoxHeight
            ));

            // 1. Central Ribbon Body (Rectangle)
            $rectBody = $dom->createElement('rect');
            $rectBody->setAttribute('x', (string) $mainRectLeftX);
            $rectBody->setAttribute('y', (string) $ribbonTopY);
            $rectBody->setAttribute('width', (string) $vbWidth);
            $rectBody->setAttribute('height', (string) $ribbonStripHeight);
            $rectBody->setAttribute('fill', 'black');
            $svgRoot->appendChild($rectBody);

            // 2. Left Tail Path
            $leftTailPathD = sprintf(
                'M %f,%f '. // Top-right of tail (connects to main rect top-left equivalent)
                'L %f,%f '. // Outer top-left of tail
                'L %f,%f '. // V-notch point (at tail's own mid-Y)
                'L %f,%f '. // Outer bottom-left of tail
                'L %f,%f '. // Bottom-right of tail (connects to main rect bottom-left equivalent)
                'Z',           // Close path
                $mainRectLeftX, $tailTopY,    // Connects to where main rect would be if shifted
                $leftTailOuterX, $tailTopY,
                $leftTailVCutX, $tailMidY,   // V-notch at tail's own middle
                $leftTailOuterX, $tailBottomY,
                $mainRectLeftX, $tailBottomY // Connects to where main rect would be if shifted
            );
            $leftTailPath = $dom->createElement('path');
            $leftTailPath->setAttribute('d', $leftTailPathD);
            $leftTailPath->setAttribute('fill', 'black');
            $svgRoot->appendChild($leftTailPath);

            // 3. Right Tail Path
            $rightTailPathD = sprintf(
                'M %f,%f '. // Top-left of tail (connects to main rect top-right equivalent)
                'L %f,%f '. // Outer top-right of tail
                'L %f,%f '. // V-notch point (at tail's own mid-Y)
                'L %f,%f '. // Outer bottom-right of tail
                'L %f,%f '. // Bottom-left of tail (connects to main rect bottom-right equivalent)
                'Z',           // Close path
                $mainRectRightX, $tailTopY,  // Connects to where main rect would be if shifted
                $rightTailOuterX, $tailTopY,
                $rightTailVCutX, $tailMidY, // V-notch at tail's own middle
                $rightTailOuterX, $tailBottomY,
                $mainRectRightX, $tailBottomY // Connects to where main rect would be if shifted
            );
            $rightTailPath = $dom->createElement('path');
            $rightTailPath->setAttribute('d', $rightTailPathD);
            $rightTailPath->setAttribute('fill', 'black');
            $svgRoot->appendChild($rightTailPath);

            // 4. "Instant Reservations" Text (on top of the central body)
            $textRibbon = $dom->createElement('text', $ribbonText);
            $textRibbon->setAttribute('x', (string) ($mainRectLeftX + ($vbWidth / 2))); // Center on main body
            $textRibbon->setAttribute('y', (string) $ribbonMidY);
            $textRibbon->setAttribute('font-family', $fontFamily);
            $textRibbon->setAttribute('font-size', (string) max(3, $ribbonTextSize)); // Lowered minimum font size to 3
            $textRibbon->setAttribute('text-anchor', 'middle');
            $textRibbon->setAttribute('dominant-baseline', 'central');
            $textRibbon->setAttribute('fill', 'white');
            $svgRoot->appendChild($textRibbon);

            $finalSvg = $dom->saveXML($svgRoot);
        } else {
            // Fallback if SVG loading fails, return the un-customized SVG or an error indicator
            // For now, just use the raw SVG before modification attempts
            $finalSvg = $rawSvgQrCode;
        }

        return [
            'image' => $qrCodeImage, // This is the base64 PNG data URI for display
            'downloadURL' => 'data:image/svg+xml;base64,'.base64_encode($finalSvg),
        ];
    }
}
