<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Actions\User\CheckUserHasBookings;
use App\Actions\User\DeleteOrSuspendUser;
use App\Actions\User\SuspendUser;
use App\Filament\Resources\UserResource;
use App\Livewire\BookingsTable;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

/**
 * @property User $record
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getFooterWidgets(): array
    {
        return [
            BookingsTable::make([
                'user' => $this->record,
                'columnSpan' => 'full',
            ]),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')
                    ->description('Basic user account information')
                    ->schema([
                        TextInput::make('first_name')
                            ->required(),
                        TextInput::make('last_name')
                            ->required(),
                        TextInput::make('email')
                            ->email()
                            ->required(),
                        TextInput::make('phone'),
                        Placeholder::make('roles')
                            ->label('Current Roles')
                            ->content(function () {
                                $coreRoles = $this->record->roles()
                                    ->whereIn('name', ['partner', 'concierge', 'super_admin', 'venue'])
                                    ->pluck('name')
                                    ->map(fn ($role) => match ($role) {
                                        'super_admin' => 'Super Admin',
                                        default => ucfirst($role)
                                    })
                                    ->join(', ');

                                return $coreRoles ?: 'No core roles assigned';
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Impersonate::make()
                    ->color('primary')
                    ->redirectTo(config('app.platform_url'))
                    ->hidden(fn () => isPrimaApp())
                    ->record($this->record),

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

                            activity()
                                ->performedOn($this->record)
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'action' => 'made_partner',
                                    'user_email' => $this->record->email,
                                    'user_name' => $this->record->first_name.' '.$this->record->last_name,
                                    'percentage' => $data['percentage'],
                                ])
                                ->log('User was made a partner');

                            Notification::make()
                                ->success()
                                ->title('Success')
                                ->body('User is now a partner')
                                ->send();

                            $this->record->refresh();
                        });
                    })
                    ->visible(fn () => ! $this->record->hasRole('partner') && ! $this->record->suspended_at),

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

                            activity()
                                ->performedOn($this->record)
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'action' => 'made_concierge',
                                    'user_email' => $this->record->email,
                                    'user_name' => $this->record->first_name.' '.$this->record->last_name,
                                    'hotel_name' => $data['hotel_name'],
                                ])
                                ->log('User was made a concierge');

                            Notification::make()
                                ->success()
                                ->title('Success')
                                ->body('User is now a concierge')
                                ->send();

                            $this->record->refresh();
                        });
                    })
                    ->visible(fn () => ! $this->record->hasRole('concierge') && ! $this->record->suspended_at),

                Action::make('makeSuperAdmin')
                    ->label('Make Super Admin')
                    ->icon('heroicon-m-key')
                    ->color('info')
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

                        activity()
                            ->performedOn($this->record)
                            ->causedBy(auth()->user())
                            ->withProperties([
                                'action' => 'made_super_admin',
                                'user_email' => $this->record->email,
                                'user_name' => $this->record->first_name.' '.$this->record->last_name,
                            ])
                            ->log('User was made a super admin');

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('User is now a super admin')
                            ->send();

                        $this->record->refresh();
                    })
                    ->visible(fn () => ! $this->record->hasRole('super_admin') && ! $this->record->suspended_at),

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

                        DB::transaction(function () {
                            // Remove the role
                            $this->record->removeRole('super_admin');

                            // Delete the role profile
                            $this->record->roleProfiles()
                                ->whereHas('role', fn (Builder $query) => $query->where('name', 'super_admin')
                                )
                                ->delete();

                            activity()
                                ->performedOn($this->record)
                                ->causedBy(auth()->user())
                                ->withProperties([
                                    'action' => 'removed_super_admin',
                                    'user_email' => $this->record->email,
                                    'user_name' => $this->record->first_name.' '.$this->record->last_name,
                                    'remaining_roles' => $this->record->roles->pluck('name')->toArray(),
                                ])
                                ->log('Super admin role was removed from user');

                            Notification::make()
                                ->success()
                                ->title('Success')
                                ->body('Super admin role has been removed')
                                ->send();

                            $this->record->refresh();
                        });
                    })
                    ->visible(fn () => $this->record->hasRole('super_admin') &&
                        $this->record->roles()
                            ->whereIn('name', ['venue', 'partner', 'concierge'])
                            ->exists() &&
                        ! $this->record->suspended_at
                    ),

                Action::make('toggleSuspension')
                    ->label(fn () => $this->record->suspended_at
                        ? 'Unsuspend User'
                        : 'Suspend User'
                    )
                    ->icon(fn () => $this->record->suspended_at
                        ? 'heroicon-m-arrow-path'
                        : 'heroicon-m-no-symbol'
                    )
                    ->color(fn () => $this->record->suspended_at ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn () => $this->record->suspended_at
                        ? 'Unsuspend User'
                        : 'Suspend User'
                    )
                    ->modalDescription(fn () => $this->record->suspended_at
                        ? 'This will restore the user\'s access to the system.'
                        : 'This will prevent the user from accessing the system.'
                    )
                    ->action(function (): void {
                        $result = SuspendUser::run($this->record);

                        Notification::make()
                            ->success()
                            ->title(match ($result['action']) {
                                'suspended' => 'User Suspended',
                                'unsuspended' => 'User Unsuspended',
                            })
                            ->body($result['message'])
                            ->send();

                        $this->record->refresh();
                    })
                    ->visible(fn () => auth()->user()->hasRole('super_admin') &&
                        auth()->id() !== $this->record->id
                    ),

                Action::make('deleteOrSuspendUser')
                    ->label('Delete User')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete User')
                    ->modalDescription('Are you sure you want to delete this user? This action cannot be undone.')
                    ->action(function (): void {
                        $result = DeleteOrSuspendUser::run($this->record);

                        Notification::make()
                            ->success()
                            ->title(match ($result['action']) {
                                'deleted' => 'User Deleted',
                                'suspended' => 'User Suspended',
                            })
                            ->body($result['message'])
                            ->send();

                        if ($result['action'] === 'deleted') {
                            $this->redirect(UserResource::getUrl());
                        } else {
                            $this->record->refresh();
                        }
                    })
                    ->visible(fn () => auth()->user()->hasRole('super_admin') &&
                        ! $this->record->suspended_at &&
                        ! CheckUserHasBookings::run($this->record) &&
                        auth()->id() !== $this->record->id
                    ),
            ])
                ->label('Manage User')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->iconButton()
                ->visible(fn () => auth()->user()->hasRole('super_admin')),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }
}
