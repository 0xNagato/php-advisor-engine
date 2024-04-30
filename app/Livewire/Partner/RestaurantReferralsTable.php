<?php

namespace App\Livewire\Partner;

use App\Events\RestaurantInvited;
use App\Filament\Pages\Partner\RestaurantEarnings;
use App\Models\Referral;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use STS\FilamentImpersonate\Impersonate;

class RestaurantReferralsTable extends BaseWidget
{
    public static ?string $heading = 'Restaurants';

    public int|string|array $columnSpan;

    protected $listeners = ['restaurant-referred' => '$refresh'];

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return User::whereHas('referral', function ($query) {
                    $query->where('type', 'restaurant')
                        ->where('referrer_id', auth()->id());
                });
            })
            ->recordUrl(function (User $record) {
                if ($record->has_secured) {
                    return RestaurantEarnings::getUrl([$record->restaurant->id]);
                }

                return null;
            })
            ->emptyStateHeading('No restaurants found.')
            ->columns([
                TextColumn::make('restaurant.restaurant_name')
                    ->label('Referral')
                    ->formatStateUsing(function (Referral $record) {
                        return view('partials.referral-info-column', ['record' => $record]);
                    }),
                IconColumn::make('has_secured')
                    ->label('Active')
                    ->alignCenter()
                    ->icon(fn (string $state): string => empty($state) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (string $state): string => empty($state) ? 'danger' : 'success'),
            ])
            ->actions([
                Impersonate::make(),
                Action::make('sendWelcome')
                    ->icon('fas-paper-plane')
                    ->iconButton()
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Welcome Email')
                    ->hidden(fn (User $record) => $record->has_secured)
                    ->action(function (User $record) {
                        RestaurantInvited::dispatch($record->restaurant);

                        $record->secured_at = now();
                        $record->save();

                        $record->referral->secured_at = now();
                        $record->referral->save();

                        $record->restaurant->is_suspended = true;
                        $record->restaurant->save();

                        Notification::make()
                            ->title('Welcome SMS sent successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
