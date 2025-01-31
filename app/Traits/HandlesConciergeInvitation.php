<?php

namespace App\Traits;

use App\Models\Concierge;
use App\Models\Partner;
use App\Models\Referral;
use App\Models\Region;
use App\Models\User;
use App\Notifications\Concierge\ConciergeCreated;
use App\Notifications\Concierge\ConciergeRegisteredEmail;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

trait HandlesConciergeInvitation
{
    public ?Referral $referral = null;

    public ?Partner $invitingPartner = null;

    public ?Concierge $invitingConcierge = null;

    public ?array $data = [];

    public ?string $invitationUsedMessage = null;

    public function getTitle(): string|Htmlable
    {
        return 'Create Your Account';
    }

    public function getHeading(): string|Htmlable
    {
        if ($this->invitationUsedMessage) {
            return '';
        }

        return 'Create Your Account';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->referral) {
            return "You were referred by: {$this->referral->referrer->name}";
        }

        $inviter = $this->invitingPartner ?? $this->invitingConcierge;

        return "You were referred by: {$inviter->user->name}";
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
                        lenient: true,
                    )
                    ->columnSpan(2)
                    ->unique(User::class, 'phone')
                    ->required(),
                TextInput::make('hotel_name')
                    ->label('Affiliation')
                    ->hiddenLabel()
                    ->placeholder('Hotel, Company or Your Name')
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
                CheckboxList::make('notification_regions')
                    ->label(new HtmlString('
                        <div class="block w-full mb-1">Notification Preferences</div>
                        <div class="block w-full mb-2 font-normal text-gray-500">Select the regions where you plan on creating bookings so we can notify you about new venues and experiences.</div>
                    '))
                    ->options(function () {
                        $regions = Region::all();
                        $orderedNames = ['Miami', 'Los Angeles', 'Ibiza', 'New York'];

                        return $regions->sortBy(function ($region) use ($orderedNames) {
                            $index = array_search($region->name, $orderedNames);

                            return $index === false ? PHP_INT_MAX : $index;
                        })
                            ->pluck('name', 'id');
                    })
                    ->columns([
                        'default' => 2,
                        'sm' => 2,
                        'md' => 2,
                        'lg' => 2,
                        'xl' => 2,
                    ])
                    ->gridDirection('row')
                    ->columnSpan(2),
                $this->getTermsAndConditionsFormComponent()
                    ->columnSpan(2),
                Checkbox::make('send_agreement_copy')
                    ->label('Email me a copy of the agreement')
                    ->columnSpan(2),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('data');
    }

    public function secureAccount(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title("Too many requests! Please wait another {$exception->secondsUntilAvailable} seconds to create your account.")
                ->danger()
                ->send();

            return;
        }

        if ($this->invitationUsedMessage) {
            Notification::make()
                ->title($this->invitationUsedMessage)
                ->danger()
                ->send();
            $this->redirect(config('app.platform_url'));
        }

        $data = $this->form->getState();

        if (! $this->referral) {
            $this->referral = Referral::query()->create([
                'referrer_id' => $this->invitingPartner?->user_id ?? $this->invitingConcierge->user_id,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'type' => 'concierge',
                'referrer_type' => $this->invitingPartner ? 'partner' : 'concierge',
            ]);
        }

        $userData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['password']),
            'secured_at' => now(),
            'notification_regions' => $data['notification_regions'],
        ];

        if ($this->referral->referrer_type === 'partner') {
            $userData['partner_referral_id'] = $this->referral->referrer->partner->id;
        } else {
            $userData['concierge_referral_id'] = $this->referral->referrer->concierge->id;
        }

        $user = User::query()->create($userData);

        $this->referral->update([
            'user_id' => $user->id,
            'secured_at' => now(),
        ]);

        Referral::query()
            ->where('id', '!=', $this->referral->id)
            ->where(function ($query) use ($data) {
                $query->where('email', $data['email'])
                    ->orWhere('phone', $data['phone']);
            })
            ->delete();

        $user->assignRole('concierge');
        $user->concierge()->create([
            'hotel_name' => $data['hotel_name'],
        ]);

        $user->notify(new ConciergeRegisteredEmail(
            sendAgreementCopy: $data['send_agreement_copy'] ?? false,
            userData: [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'date' => now()->format('F j, Y'),
            ]
        ));

        $user->notify(new ConciergeCreated($user));

        Auth::login($user);
        $this->redirect(config('app.platform_url'));
    }

    protected function getTermsAndConditionsFormComponent(): Placeholder
    {
        $label = new HtmlString("
            <div class='px-3 py-1 text-xs text-center border border-indigo-600 rounded-lg bg-indigo-50'>
                By creating your PRIMA Concierge Account, you are agreeing to our Terms Of Service.
            </div>
            <div class='mt-2 text-xs font-bold text-center text-indigo-800 underline cursor-pointer' x-data='{}' @click='\$dispatch(\"open-modal\", { id: \"concierge-modal\" })'>
                Click Here To See Concierge Terms And Conditions
            </div>
        ");

        return Placeholder::make('termsAndConditions')
            ->content($label)
            ->hiddenLabel();
    }
}
