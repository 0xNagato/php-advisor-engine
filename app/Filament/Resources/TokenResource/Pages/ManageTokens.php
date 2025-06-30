<?php

namespace App\Filament\Resources\TokenResource\Pages;

use App\Filament\Resources\TokenResource;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageTokens extends ManageRecords
{
    protected static string $resource = TokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth('md')
                ->form([
                    Select::make('user_id')
                        ->label('Concierge')
                        ->options(fn () => User::query()
                            ->orderBy('first_name')->get()->pluck('name', 'id')
                        )
                        ->searchable(['first_name', 'last_name'])
                        ->preload(),
                    TextInput::make('token_name')
                        ->required(),
                    DatePicker::make('expires_at'),
                ])
                ->action(function (array $data) {
                    $user = User::query()->findOrFail($data['user_id']);
                    $expiresAt = $data['expires_at'] ? Carbon::createFromFormat('Y-m-d', $data['expires_at']) : null;
                    $plainTextToken = $user->createToken(
                        $data['token_name'],
                        ['*'],
                        $expiresAt
                    )->plainTextToken;

                    $this->replaceMountedAction('showToken', [
                        'token' => $plainTextToken,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Token has been created')
                        ->send();
                }),
        ];
    }

    public function showTokenAction(): Action
    {
        return Action::make('token')
            ->fillForm(fn (array $arguments) => [
                'token' => $arguments['token'],
            ])
            ->form([
                TextInput::make('token')
                    ->helperText('This is token will be show only once. Please copy it now.'),
            ])
            ->modalHeading('Copy Access Token')
            ->modalIcon('heroicon-o-key')
            ->modalAlignment('center')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->closeModalByClickingAway(false);
    }
}
