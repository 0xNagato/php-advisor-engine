<?php

namespace App\Filament\Pages\Concierge;

use App\Events\ConciergeReferredViaEmail;
use App\Events\ConciergeReferredViaText;
use App\Models\Referral;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

    protected static ?int $navigationSort = 2;

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
                                        }),
                                ])
                                    ->fullWidth(),
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
                                        }),
                                ])
                                    ->fullWidth(),
                            ])
                            ->statePath('phoneData')
                            ->columns([
                                'default' => '1',
                            ]),
                    ]),
            ]);
    }

    public function sendInviteViaEmail(): void
    {
        $data = $this->emailForm->getState();

        $referral = Referral::create([
            'referrer_id' => auth()->id(),
            'email' => $data['email'],
            'type' => 'concierge',
            'referrer_type' => strtolower(auth()->user()->main_role),
        ]);

        $this->emailForm->fill();

        ConciergeReferredViaEmail::dispatch($referral);

        $this->dispatch('concierge-referred');

        Notification::make()
            ->title('Invite sent successfully.')
            ->success()
            ->send();
    }

    public function sendInviteViaText(): void
    {
        $data = $this->textForm->getState();

        $referral = Referral::create([
            'referrer_id' => auth()->id(),
            'phone' => $data['phone'],
            'type' => 'concierge',
            'referrer_type' => strtolower(auth()->user()->main_role),
        ]);

        $this->textForm->fill();

        ConciergeReferredViaText::dispatch($referral);

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
