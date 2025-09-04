<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Actions\QrCode\GenerateQrCodes;
use App\Filament\Resources\QrCodeResource;
use App\Models\Concierge;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
                        ->placeholder('Leave empty to redirect to concierge invitation form')
                        ->url()
                        ->helperText('If left empty, QR codes will redirect to invitation form until assigned to a concierge'),
                    Select::make('referrer_concierge_id')
                        ->label('Referrer Concierge (optional)')
                        ->placeholder('Select a concierge')
                        ->options(Concierge::query()->with('user')->get()->mapWithKeys(fn ($concierge) => [$concierge->id => "{$concierge->user->name} ({$concierge->hotel_name})"]))
                        ->default(function () {
                            // Check if the current user has a concierge account
                            $currentUser = auth()->user();
                            if ($currentUser && $currentUser->concierge) {
                                return $currentUser->concierge->id;
                            }

                            return null;
                        })
                        ->searchable()
                        ->helperText('Pre-select a concierge as the referrer for invitation forms'),
                ])
                ->action(function (array $data): void {
                    $count = (int) $data['count'];
                    $prefix = $data['prefix'] ?? null;
                    $destination = $data['destination'] ?? '';
                    $referrerConciergeId = $data['referrer_concierge_id'] ?? null;

                    // Generate the QR codes
                    $qrCodes = app(GenerateQrCodes::class)->handle(
                        count: $count,
                        defaultDestination: $destination,
                        prefix: $prefix,
                        referrerConciergeId: $referrerConciergeId
                    );

                    Notification::make()
                        ->title("{$qrCodes->count()} QR codes generated successfully!")
                        ->success()
                        ->send();
                }),
        ];
    }
}
