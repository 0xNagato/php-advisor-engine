<?php

namespace App\Livewire\Partner;

use App\Enums\VenueStatus;
use App\Filament\Pages\Partner\VenueEarnings;
use App\Models\User;
use App\Notifications\Venue\SendWelcomeText;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Database\Query\Builder;
use STS\FilamentImpersonate\Impersonate;

class VenueReferralsTable extends BaseWidget
{
    public static ?string $heading = 'Venues';

    public int|string|array $columnSpan;

    protected $listeners = ['venue-referred' => '$refresh'];

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => User::query()->whereHas('referral', static function (Builder $query) {
                $query->where('type', 'venue')
                    ->where('referrer_id', auth()->id());
            }))
            ->recordUrl(function (User $record) {
                if ($record->has_secured) {
                    return VenueEarnings::getUrl([$record->venue->id]);
                }

                return null;
            })
            ->emptyStateHeading('No venues found.')
            ->columns([
                TextColumn::make('venue.name')
                    ->label('Venue')
                    ->formatStateUsing(fn (User $record) => view('partials.venue-referral-info-column', ['record' => $record])),
                IconColumn::make('has_secured')
                    ->label('Active')
                    ->alignCenter()
                    ->icon(fn (string $state): string => blank($state) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (string $state): string => blank($state) ? 'danger' : 'success'),
            ])
            ->paginated([5, 10, 25])
            ->actions([
                Impersonate::make()
                    ->redirectTo(config('app.platform_url')),
                Action::make('sendWelcome')
                    ->icon('fas-paper-plane')
                    ->iconButton()
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Welcome Email')
                    ->hidden(fn (User $record) => $record->has_secured)
                    ->action(function (User $record) {
                        $record->venue->user->notify(new SendWelcomeText);

                        $record->secured_at = now();
                        $record->save();

                        $record->referral->secured_at = now();
                        $record->referral->save();

                        $record->venue->status = VenueStatus::PENDING;
                        $record->venue->save();

                        Notification::make()
                            ->title('Welcome SMS sent successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
