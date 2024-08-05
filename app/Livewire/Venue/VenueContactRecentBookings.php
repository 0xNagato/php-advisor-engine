<?php

namespace App\Livewire\Venue;

use App\Filament\Actions\Venue\MarkAsNoShowAction;
use App\Models\Booking;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class VenueContactRecentBookings extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    #[Url(keep: true)]
    public string $venue;

    public ?string $name = null;

    protected ?string $heading = 'Bookings';

    public function mount(): void
    {
        abort_unless(request()?->hasValidSignatureWhileIgnoring(['page']), 403, 'Invalid signature.');
    }

    public function getHeading(): ?string
    {
        return $this->name ? (string) $this->name : $this->heading;
    }

    public function table(Table $table): Table
    {
        $query = Booking::confirmedOrNoShow()
            ->with('venue')
            ->whereHas('venue', function ($query) {
                $query->where('venues.id', $this->venue);
            });

        $this->name = $query->first()->venue->name;

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('id')
                    ->label('Booking')
                    ->formatStateUsing(
                        fn (Booking $record) => view('partials.booking-info-column-contact', [
                            'record' => $record,
                        ])
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    MarkAsNoShowAction::make('mark no show'),
                ])
                    ->tooltip('Actions'),
            ]);
    }

    public function render(): View
    {
        return view('livewire.venue.bookings');
    }
}
