<?php

namespace App\Livewire;

use App\Models\ConciergeReferral;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * @property Form $form
 */
class ConciergeInvitation extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'livewire.concierge-invitation';

    protected static string $layout = 'components.layouts.app';

    public ConciergeReferral $conciergeReferral;

    public ?array $data = [];

    public function getTitle(): string|Htmlable
    {
        return 'Secure Your Account';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Secure Your Account';
    }

    public function mount(ConciergeReferral $conciergeReferral): void
    {
        clock($conciergeReferral); // ConciergeReferral {#1234}
        $this->conciergeReferral = $conciergeReferral;
        $this->form->fill([
            'email' => $conciergeReferral->email,
            'phone' => $conciergeReferral->phone,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('first_name')
                    ->hiddenLabel()
                    ->placeholder('First Name')
                    ->required(),
                TextInput::make('last_name')
                    ->hiddenLabel()
                    ->placeholder('Last Name')
                    ->required(),
                TextInput::make('email')
                    ->hiddenLabel()
                    ->placeholder('Email Address')
                    ->unique(User::class)
                    ->type('email')
                    ->columnSpan(2)
                    ->required(),
                PhoneInput::make('phone')
                    ->hiddenLabel()
                    ->columnSpan(2)
                    ->required(),
                TextInput::make('hotel_name')
                    ->label('Affiliation')
                    ->hiddenLabel()
                    ->placeholder('Hotel Name or Company Name')
                    ->columnSpan(2)
                    ->required(),
                TextInput::make('password')
                    ->hiddenLabel()
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->rule(PasswordRule::default())
                    ->placeholder('Password')
                    ->columnSpan(2)
                    ->same('passwordConfirmation'),
                TextInput::make('passwordConfirmation')
                    ->hiddenLabel()
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->placeholder('Confirm Password')
                    ->columnSpan(2)
                    ->dehydrated(false),
                $this->getTermsAndConditionsFormComponent()
                    ->columnSpan(2),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('data');
    }

    protected function getTermsAndConditionsFormComponent(): Placeholder
    {
        $label = new HtmlString("
            <div class='font-bold text-center text-indigo-800 underline cursor-pointer' x-data='{}' @click='\$dispatch(\"open-modal\", { id: \"concierge-modal\" })'>
                Secure Your Account to Accept PRIMA concierge Terms and Conditions
            </div>
        ");

        return Placeholder::make('termsAndConditions')
            ->content($label)
            ->hiddenLabel();
    }

    public function secureAccount(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title("Too many requests! Please wait another $exception->secondsUntilAvailable seconds to secure your account.")
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['password']),
            'concierge_referral_id' => $this->conciergeReferral->concierge_id,
            'secured_at' => now(),
        ]);

        $this->conciergeReferral->update([
            'user_id' => $user->id,
            'secured_at' => now(),
        ]);

        $user->assignRole('concierge');

        $user->concierge()->create([
            'hotel_name' => $data['hotel_name'],
        ]);

        Auth::login($user);

        $this->redirectRoute('filament.admin.pages.concierge.reservation-hub');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('secureAccount')
                ->label(__('Secure Account'))
                ->color('indigo')
                ->submit('secureAccount'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
