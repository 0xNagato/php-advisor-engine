<?php

namespace App\Livewire\Concierge;

use App\Models\Referral;
use App\Models\User;
use App\Notifications\Concierge\ConciergeRegisteredEmail;
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
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * @property Form $form
 */
class ConciergeInvitation extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'livewire.concierge-invitation';

    protected static string $layout = 'components.layouts.app';

    public Referral $referral;

    public ?array $data = [];

    public function getTitle(): string|Htmlable
    {
        return 'Create Your Account';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Create Your Account';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return "Referral from {$this->referral->referrer->name}";
    }

    public function mount(Referral $referral): void
    {
        $this->referral = $referral;
        $this->form->fill([
            'first_name' => $referral->first_name,
            'last_name' => $referral->last_name,
            'email' => $referral->email,
            'phone' => $referral->phone,
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
                    ->onlyCountries(config('app.countries'))
                    ->displayNumberFormat(PhoneInputNumberType::E164)
                    ->disallowDropdown()
                    ->validateFor(
                        country: config('app.countries'),
                        type: PhoneNumberType::MOBILE,
                        lenient: true,
                    )
                    ->columnSpan(2)
                    ->required(),
                TextInput::make('hotel_name')
                    ->label('Affiliation')
                    ->hiddenLabel()
                    ->placeholder('Hotel Name, Company Name or Your Name')
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
                Create Your Account to Accept PRIMA Concierge Terms and Conditions
            </div>
        ");

        return Placeholder::make('termsAndConditions')
            ->content($label)
            ->hiddenLabel();
    }

    public function secureAccount(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title("Too many requests! Please wait another $exception->secondsUntilAvailable seconds to create your account.")
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        $referrer = User::query()->findOrFail($this->referral->referrer_id);
        $role = strtolower($referrer->main_role);

        $userData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['password']),
            'secured_at' => now(),
        ];

        if ($role === 'partner') {
            $userData['partner_referral_id'] = $referrer->partner->id;
        } else {
            $userData['concierge_referral_id'] = $referrer->concierge->id;
        }

        $user = User::query()->create($userData);

        $this->referral->update([
            'user_id' => $user->id,
            'secured_at' => now(),
        ]);

        $user->assignRole('concierge');

        $user->concierge()->create([
            'hotel_name' => $data['hotel_name'],
        ]);

        $user->notify(new ConciergeRegisteredEmail);

        Auth::login($user);

        $this->redirect('/');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('secureAccount')
                ->label(__('Create Your Account'))
                ->color('indigo')
                ->submit('secureAccount'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
