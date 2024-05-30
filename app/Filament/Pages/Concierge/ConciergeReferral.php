<?php

namespace App\Filament\Pages\Concierge;

use App\Events\ConciergeReferredViaEmail;
use App\Events\ConciergeReferredViaText;
use App\Models\Referral;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * @property Form $tabbedForm
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
        if (session()->exists('simpleMode')) {
            return ! session('simpleMode');
        }

        return auth()->user()->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->tabbedForm->fill();
    }

    public function tabbedForm(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('tabs')
                    ->extraAttributes(['class' => 'single-col-tabs'])
                    ->tabs([
                        Tab::make('Send SMS')
                            ->icon('gmdi-phone-android-o')
                            ->schema([
                                TextInput::make('first_name')
                                    ->hiddenLabel()
                                    ->live()
                                    ->columnSpan(1)
                                    ->extraAttributes(['class' => 'mr-2'])
                                    ->placeholder('First Name'),
                                TextInput::make('last_name')
                                    ->hiddenLabel()
                                    ->live()
                                    ->columnSpan(1)
                                    ->placeholder('Last Name'),
                                PhoneInput::make('phone')
                                    ->live()
                                    ->displayNumberFormat(PhoneInputNumberType::E164)
                                    ->disallowDropdown()
                                    ->placeholder('Phone Number')
                                    ->onlyCountries(config('app.countries'))
                                    ->validateFor(
                                        country: config('app.countries'),
                                        type: PhoneNumberType::MOBILE,
                                        lenient: true,
                                    )
                                    ->validationMessages([
                                        'validation.phone' => 'The phone number is invalid.',
                                    ])
                                    ->columnSpan(2)
                                    ->hiddenLabel(),
                                Actions::make([
                                    Action::make('sendText')
                                        ->label('Send SMS')
                                        ->disabled(fn (Get $get) => blank($get('phone')) || $get('first_name') === '' || $get('last_name') === '')
                                        ->action(function () {
                                            $this->sendInviteViaText();
                                        }),
                                ])->columnSpanFull()
                                    ->fullWidth(),
                            ])
                            ->statePath('phoneData')
                            ->columns([
                                'default' => '2',
                            ]),
                        Tab::make('Send Email')
                            ->icon('gmdi-email-o')
                            ->schema([
                                TextInput::make('first_name')
                                    ->live()
                                    ->hiddenLabel()
                                    ->columnSpan(1)
                                    ->extraAttributes(['class' => 'mr-2'])
                                    ->placeholder('First Name'),
                                TextInput::make('last_name')
                                    ->live()
                                    ->hiddenLabel()
                                    ->columnSpan(1)
                                    ->placeholder('Last Name'),
                                TextInput::make('email')
                                    ->live()
                                    ->placeholder('Email Address')
                                    ->unique(User::class, 'email')
                                    ->type('email')
                                    ->columnSpan(2)
                                    ->hiddenLabel(),
                                Actions::make([
                                    Action::make('sendEmail')
                                        ->label('Send Email')
                                        ->disabled(fn (Get $get) => blank($get('email')) || $get('first_name') === '' || $get('last_name') === '')
                                        ->action(function () {
                                            $this->sendInviteViaEmail();
                                        }),
                                ])
                                    ->columnSpanFull()
                                    ->fullWidth(),
                            ])
                            ->statePath('emailData')
                            ->columns([
                                'default' => '2',
                            ]),
                    ]),
            ]);
    }

    public function sendInviteViaEmail(): void
    {
        $data = $this->tabbedForm->getState()['emailData'];

        $referral = Referral::query()->create([
            'referrer_id' => auth()->id(),
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'type' => 'concierge',
            'referrer_type' => strtolower(auth()->user()->main_role),
        ]);

        $this->tabbedForm->fill();

        ConciergeReferredViaEmail::dispatch($referral);

        $this->dispatch('concierge-referred');

        Notification::make()
            ->title('Invite sent successfully.')
            ->success()
            ->send();
    }

    public function sendInviteViaText(): void
    {
        $data = $this->tabbedForm->getState()['phoneData'];

        $referral = Referral::query()->create([
            'referrer_id' => auth()->id(),
            'phone' => $data['phone'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'type' => 'concierge',
            'referrer_type' => strtolower(auth()->user()->main_role),
        ]);

        $this->tabbedForm->fill();

        ConciergeReferredViaText::dispatch($referral);

        $this->dispatch('concierge-referred');

        Notification::make()
            ->title('Invite sent successfully.')
            ->success()
            ->send();
    }

    protected function getForms(): array
    {
        return [
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
