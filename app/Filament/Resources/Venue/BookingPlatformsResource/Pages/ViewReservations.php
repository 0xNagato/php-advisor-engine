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
                match ($this->record->platform_type) {
                    'covermanager' => $this->record->venue->coverManagerReservations()->getQuery()->with(['booking' => function ($query) {
                        $query->select('id', 'guest_first_name', 'guest_last_name', 'guest_count', 'booking_at');
                    }]),
                    'restoo' => $this->record->venue->restooReservations()->getQuery()->with(['booking' => function ($query) {
                        $query->select('id', 'guest_first_name', 'guest_last_name', 'guest_count', 'booking_at');
                    }]),
                    default => throw new \InvalidArgumentException("Unknown platform type: {$this->record->platform_type}"),
                }
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
            $baseColumns[] = TextColumn::make('reservation_date')
                ->label('Date')
                ->date('M j, Y')
                ->sortable();
            $baseColumns[] = TextColumn::make('reservation_time')
                ->label('Time')
                ->time('g:i A')
                ->sortable();
        } elseif ($this->record->platform_type === 'restoo') {
            $baseColumns[] = TextColumn::make('reservation_datetime')
                ->label('Date & Time')
                ->dateTime('M j, Y g:i A')
                ->sortable();
        }

        if ($this->record->platform_type === 'covermanager') {
            $baseColumns[] = TextColumn::make('covermanager_reservation_id')
                ->label('CoverManager ID')
                ->searchable();
            $baseColumns[] = TextColumn::make('covermanager_status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'cancelled' => 'danger',
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    default => 'gray',
                });
        } elseif ($this->record->platform_type === 'restoo') {
            $baseColumns[] = TextColumn::make('restoo_reservation_id')
                ->label('Restoo ID')
                ->searchable();
            $baseColumns[] = TextColumn::make('restoo_status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'cancelled' => 'danger',
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    default => 'gray',
                });
        }

        $baseColumns[] = TextColumn::make('created_at')
            ->label('Synced At')
            ->dateTime()
            ->sortable();

        return $baseColumns;
    }
}
