<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

/**
 * @property User $record
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required(),
                TextInput::make('phone'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('makePartner')
                ->label('Make Partner')
                ->icon('heroicon-m-briefcase')
                ->color('warning')
                ->modalHeading('Create Partner Profile')
                ->form([
                    TextInput::make('percentage')
                        ->label('Commission Percentage')
                        ->numeric()
                        ->default(20)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data) {
                        if ($this->record->hasRole('partner')) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('User is already a partner')
                                ->send();

                            return;
                        }

                        Partner::query()->create([
                            'user_id' => $this->record->id,
                            'percentage' => $data['percentage'],
                        ]);
                        $this->record->assignRole('partner');

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('User is now a partner')
                            ->send();

                        // Refresh the record to update the UI
                        $this->record->refresh();
                    });
                })
                ->visible(fn () => ! $this->record->hasRole('partner')),

            Action::make('makeConcierge')
                ->label('Make Concierge')
                ->icon('heroicon-m-building-office')
                ->color('success')
                ->modalHeading('Create Concierge Profile')
                ->form([
                    TextInput::make('hotel_name')
                        ->label('Hotel Name')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data) {
                        if ($this->record->hasRole('concierge')) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('User is already a concierge')
                                ->send();

                            return;
                        }

                        Concierge::query()->create([
                            'user_id' => $this->record->id,
                            'hotel_name' => $data['hotel_name'],
                        ]);
                        $this->record->assignRole('concierge');

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('User is now a concierge')
                            ->send();

                        // Refresh the record to update the UI
                        $this->record->refresh();
                    });
                })
                ->visible(fn () => ! $this->record->hasRole('concierge')),

            Action::make('makeSuperAdmin')
                ->label('Make Super Admin')
                ->icon('heroicon-m-key')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Make User Super Admin')
                ->modalDescription('Are you sure you want to make this user a Super Admin? This gives them full access to the system.')
                ->action(function (): void {
                    if ($this->record->hasRole('super_admin')) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('User is already a super admin')
                            ->send();

                        return;
                    }

                    $this->record->assignRole('super_admin');

                    Notification::make()
                        ->success()
                        ->title('Success')
                        ->body('User is now a super admin')
                        ->send();

                    // Refresh the record to update the UI
                    $this->record->refresh();
                })
                ->visible(fn () => ! $this->record->hasRole('super_admin')),

            Action::make('removeSuperAdmin')
                ->label('Remove Super Admin')
                ->icon('heroicon-m-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Remove Super Admin Role')
                ->modalDescription('Are you sure you want to remove super admin access from this user?')
                ->action(function (): void {
                    $coreRoles = $this->record->roles()
                        ->whereIn('name', ['super_admin', 'venue', 'partner', 'concierge'])
                        ->count();

                    if ($coreRoles <= 1) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('User must have at least one other core role before removing super admin')
                            ->send();

                        return;
                    }

                    $this->record->removeRole('super_admin');

                    Notification::make()
                        ->success()
                        ->title('Success')
                        ->body('Super admin role has been removed')
                        ->send();

                    $this->record->refresh();
                })
                ->visible(fn () => $this->record->hasRole('super_admin') &&
                    $this->record->roles()
                        ->whereIn('name', ['venue', 'partner', 'concierge'])
                        ->exists()
                ),
        ];
    }
}
