<?php

namespace App\Filament\Exports;

use App\Models\PaymentItem;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EarningExporter extends Exporter
{
    protected static ?string $model = PaymentItem::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user_id'),
            ExportColumn::make('user.first_name')
                ->label('First Name'),
            ExportColumn::make('user.last_name')
                ->label('Last Name'),
            ExportColumn::make('user.phone')
                ->label('Phone'),
            ExportColumn::make('user.address_1')
                ->label('Address 1'),
            ExportColumn::make('user.address_2')
                ->label('Address 2'),
            ExportColumn::make('user.city')
                ->label('City'),
            ExportColumn::make('user.state')
                ->label('State'),
            ExportColumn::make('user.zip')
                ->label('Zip'),
            ExportColumn::make('user.country')
                ->label('Country'),
            ExportColumn::make('user.region')
                ->label('Region'),
            ExportColumn::make('user.payout.payout_name')
                ->label('Payout Name'),
            ExportColumn::make('user.payout.payout_type')
                ->label('Payout Type'),
            ExportColumn::make('user.payout.account_type')
                ->label('Account Type'),
            ExportColumn::make('user.payout.account_number')
                ->label('Account Number'),
            ExportColumn::make('user.payout.routing_number')
                ->label('Routing Number'),
            ExportColumn::make('amount')
                ->state(fn(PaymentItem $item) => money($item->amount, $item->currency)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your earning export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public function getFormats(): array
    {
        return [
            ExportFormat::Csv,
            ExportFormat::Xlsx,
        ];
    }
}
