<?php

namespace App\Filament\Resources\Venue\BookingPlatformsResource\Pages;

use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Filament\Resources\Venue\BookingPlatformsResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewReservations extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = BookingPlatformsResource::class;

    protected static string $view = 'filament.resources.venue.booking-platforms-resource.pages.view-reservations';

    public function getTitle(): string
    {
        $platformName = match ($this->record->platform_type) {
            'covermanager' => 'CoverManager',
            'restoo' => 'Restoo',
            default => ucfirst($this->record->platform_type),
        };

        return "{$platformName} Reservations - {$this->record->venue->name}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->record->venue->platformReservations()
                    ->where('platform_type', $this->record->platform_type)
                    ->with(['booking' => function ($query) {
                        $query->select('id', 'guest_first_name', 'guest_last_name', 'guest_count', 'booking_at');
                    }])
                    ->getQuery()
            )
            ->columns($this->getColumnsForPlatform())
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No reservations found')
            ->emptyStateDescription('This platform doesn\'t have any reservations yet.')
            ->recordUrl(function ($record) {
                if ($record->booking_id) {
                    return ViewBooking::getUrl(['record' => $record->booking_id]);
                }

                return null;
            });
    }

    protected function getColumnsForPlatform(): array
    {
        $baseColumns = [
            TextColumn::make('booking.id')
                ->label('Booking ID')
                ->sortable()
                ->searchable(),
            TextColumn::make('customer_name')
                ->label('Customer')
                ->searchable()
                ->sortable()
                ->placeholder('No name'),
            TextColumn::make('party_size')
                ->label('Party Size')
                ->sortable()
                ->alignCenter(),
        ];

        // Add platform-specific date/time columns
        if ($this->record->platform_type === 'covermanager') {
            $baseColumns[] = TextColumn::make('platform_data')
                ->label('Date')
                ->getStateUsing(fn ($record) => $record->platform_data['reservation_date'] ?? null)
                ->date('M j, Y')
                ->sortable(false);
            $baseColumns[] = TextColumn::make('platform_data')
                ->label('Time')
                ->getStateUsing(fn ($record) => $record->platform_data['reservation_time'] ?? null)
                ->formatStateUsing(fn ($state) => $state ? date('g:i A', strtotime((string) $state)) : null)
                ->sortable(false);
        } elseif ($this->record->platform_type === 'restoo') {
            $baseColumns[] = TextColumn::make('reservation_datetime')
                ->label('Date & Time')
                ->dateTime('M j, Y g:i A')
                ->sortable();
        }

        $baseColumns[] = TextColumn::make('platform_reservation_id')
            ->label(match ($this->record->platform_type) {
                'covermanager' => 'CoverManager ID',
                'restoo' => 'Restoo ID',
                default => 'Platform ID',
            })
            ->searchable();

        $baseColumns[] = TextColumn::make('platform_status')
            ->label('Status')
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'cancelled' => 'danger',
                'confirmed' => 'success',
                'pending' => 'warning',
                default => 'gray',
            });

        $baseColumns[] = TextColumn::make('created_at')
            ->label('Synced At')
            ->dateTime()
            ->sortable();

        return $baseColumns;
    }
}
