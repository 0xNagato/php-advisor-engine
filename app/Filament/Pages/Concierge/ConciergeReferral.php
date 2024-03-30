<?php

namespace App\Filament\Pages\Concierge;

use App\Events\ConciergeReferredViaEmail;
use App\Events\ConciergeReferredViaText;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * @property Form $textForm
 * @property Form $emailForm
 */
class ConciergeReferral extends Page
{
    protected static ?string $navigationIcon = 'gmdi-people-alt-tt';

    protected static string $view = 'filament.pages.concierge.concierge-referral';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'My Referrals';

    public ?array $emailData = [];

    public ?array $phoneData = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->emailForm->fill();
        $this->textForm->fill();
    }

    public function tabbedForm(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('tabs')
                    ->extraAttributes(['class' => 'single-col-tabs'])
                    ->tabs([
                        Tabs\Tab::make('Send Email')
                            ->icon('gmdi-email-o')
                            ->schema([
                                TextInput::make('email')
                                    ->placeholder('Email Address')
                                    ->unique(User::class, 'email')
                                    ->type('email')
                                    ->columnSpan(2)
                                    ->required()
                                    ->hiddenLabel(),
                                Actions::make([
                                    Action::make('sendEmail')
                                        ->label('Send Email')
                                        ->action(function () {
                                            $this->sendInviteViaEmail();
                                        })
                                ])
                                    ->fullWidth()
                            ])
                            ->statePath('emailData')
                            ->columns([
                                'default' => '1',
                            ]),
                        Tabs\Tab::make('Send SMS')
                            ->icon('gmdi-phone-android-o')
                            ->schema([
                                PhoneInput::make('phone')
                                    ->placeholder('Phone Number')
                                    ->validateFor(['US', 'CA'])
                                    ->columnSpan(2)
                                    ->required()
                                    ->hiddenLabel(),
                                Actions::make([
                                    Action::make('sendText')
                                        ->label('Send SMS')
                                        ->action(function () {
                                            $this->sendInviteViaText();
                                        })
                                ])
                                    ->fullWidth()
                            ])
                            ->statePath('phoneData')
                            ->columns([
                                'default' => '1',
                            ])
                    ])
            ]);
    }

    public function sendInviteViaEmail(): void
    {
        $data = $this->emailForm->getState();

        $conciergeReferral = \App\Models\ConciergeReferral::create([
            'concierge_id' => auth()->user()->concierge->id,
            'email' => $data['email'],
        ]);

        $this->emailForm->fill();

        ConciergeReferredViaEmail::dispatch($conciergeReferral);

        $this->dispatch('concierge-referred');

        Notification::make()
            ->title('Invite sent successfully.')
            ->success()
            ->send();
    }

    public function sendInviteViaText(): void
    {
        $data = $this->textForm->getState();

        $conciergeReferral = \App\Models\ConciergeReferral::create([
            'concierge_id' => auth()->user()->concierge->id,
            'phone' => $data['phone'],
        ]);

        $this->textForm->fill();

        ConciergeReferredViaText::dispatch($conciergeReferral);

        $this->dispatch('concierge-referred');

        Notification::make()
            ->title('Invite sent successfully.')
            ->success()
            ->send();
    }

    public function emailForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->placeholder('Email Address')
                    ->unique(User::class, 'email')
                    ->type('email')
                    ->columnSpan(2)
                    ->required()
                    ->hiddenLabel(),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('emailData');
    }

    public function textForm(Form $form): Form
    {
        return $form
            ->schema([
                PhoneInput::make('phone')
                    ->placeholder('Phone Number')
                    ->validateFor(['US', 'CA'])
                    ->columnSpan(2)
                    ->required()
                    ->hiddenLabel(),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('phoneData');
    }

    protected function createUser(array $data): User
    {
        $userData = [
            'first_name' => '',
            'last_name' => '',
            'password' => bcrypt(Str::random(8)),
            'concierge_referral_id' => auth()->user()->concierge->id,
        ];

        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }

        if (isset($data['phone'])) {
            $userData['phone'] = $data['phone'];
        }

        $user = User::create($userData);

        $user->assignRole('concierge');

        $user->concierge()->create([
            'hotel_name' => '',
        ]);

        return $user;
    }

    protected function getForms(): array
    {
        return [
            'emailForm',
            'textForm',
            'tabbedForm',
        ];
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
    }
}
