<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Actions\QrCode\GenerateQrCodes;
use App\Filament\Resources\QrCodeResource;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListQrCodes extends ListRecords
{
    protected static string $resource = QrCodeResource::class;

    protected static ?string $title = 'QR Code Management';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_bulk')
                ->label('Generate Bulk QR Codes')
                ->icon('heroicon-m-qr-code')
                ->form([
                    TextInput::make('count')
                        ->label('Number of QR Codes')
                        ->numeric()
                        ->default(30)
                        ->minValue(1)
                        ->maxValue(100)
                        ->required(),
                    TextInput::make('prefix')
                        ->label('URL Key Prefix (optional)')
                        ->placeholder('e.g., event-2025')
                        ->helperText('Will be slugified and prepended to the random URL key'),
                    TextInput::make('destination')
                        ->label('Default Destination URL (optional)')
                        ->placeholder('Leave empty to use VIP calendar')
                        ->url(),
                ])
                ->action(function (array $data): void {
                    $count = (int) $data['count'];
                    $prefix = $data['prefix'] ?? null;
                    $destination = $data['destination'] ?? '';

                    // Generate the QR codes
                    $qrCodes = app(GenerateQrCodes::class)->handle(
                        count: $count,
                        defaultDestination: $destination,
                        prefix: $prefix
                    );

                    Notification::make()
                        ->title("{$qrCodes->count()} QR codes generated successfully!")
                        ->success()
                        ->send();
                }),
        ];
    }
}
