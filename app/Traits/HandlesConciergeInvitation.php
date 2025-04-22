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
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

trait HandlesConciergeInvitation
{
    public ?Referral $referral = null;

    public ?Partner $invitingPartner = null;

    public ?Concierge $invitingConcierge = null;

    public ?User $invitingVenueManager = null;

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
            // If using a standard referral, the referrer is always a User
            return "You were referred by: {$this->referral->referrer->name}";
        }

        // Handle direct link invitations
        $inviter = $this->invitingPartner ?? $this->invitingConcierge ?? $this->invitingVenueManager ?? null;

        if (! $inviter) {
            return null;
        }

        // Determine the inviter's name based on their type
        $inviterName = match (true) {
            $inviter instanceof Partner => $inviter->user?->name,
            $inviter instanceof Concierge => $inviter->user?->name,
            $inviter instanceof User => $inviter->name, // Access name directly for User (Venue Manager)
            default => null,
        };

        return $inviterName ? "You were referred by: {$inviterName}" : null;
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

                        return $regions->sortBy(function ($region) {
                            $orderedNames = ['Miami', 'Los Angeles', 'Ibiza', 'New York'];
                            $index = array_search($region->name, $orderedNames, true);

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

    /**
     * @throws Throwable
     */
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

        if ($this->invitationUsedMessage) {
            Notification::make()
                ->title($this->invitationUsedMessage)
                ->danger()
                ->send();
            $this->redirect(config('app.platform_url'));

            return;
        }

        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            if (! $this->referral) {
                $referrerType = match (true) {
                    ! is_null($this->invitingPartner) => 'partner',
                    ! is_null($this->invitingConcierge) => 'concierge',
                    property_exists($this, 'invitingVenueManager') && ! is_null($this->invitingVenueManager) => 'venue_manager',
                    default => null,
                };
                $referrerId = $this->invitingPartner?->user_id ?? $this->invitingConcierge?->user_id ?? $this->invitingVenueManager?->id ?? null;

                if (! $referrerType || ! $referrerId) {
                    throw new Exception('Could not determine referrer for direct invitation.');
                }

                $inviterRegion = match ($referrerType) {
                    'partner' => $this->invitingPartner?->user?->region,
                    'concierge' => $this->invitingConcierge?->user?->region,
                    'venue_manager' => $this->invitingVenueManager?->region,
                    default => null,
                } ?? config('app.default_region');

                $this->referral = Referral::query()->create([
                    'referrer_id' => $referrerId,
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'type' => 'concierge',
                    'referrer_type' => $referrerType,
                    'region_id' => $inviterRegion,
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

            // Set the appropriate referral ID based on referrer type
            if ($this->referral->referrer_type === 'partner') {
                $userData['partner_referral_id'] = $this->referral->referrer->partner->id;
            } elseif ($this->referral->referrer_type === 'concierge') {
                $userData['concierge_referral_id'] = $this->referral->referrer->concierge->id;
            } elseif ($this->referral->referrer_type === 'venue_manager') {
                $userData['venue_group_id'] = $this->referral->referrer?->currentVenueGroup()?->id;
            }

            $user = User::query()->create([
                ...$userData,
                'region' => $this->referral->region_id ?? $data['notification_regions'][0],
            ]);

            $this->referral->update([
                'user_id' => $user->id,
                'secured_at' => now(),
            ]);

            // Delete any other referrals with the same email or phone
            Referral::query()
                ->where('id', '!=', $this->referral->id)
                ->where(function ($query) use ($data) {
                    $query->where('email', $data['email'])
                        ->orWhere('phone', $data['phone']);
                })
                ->delete();

            $user->assignRole('concierge');

            $conciergeData = [
                'hotel_name' => $data['hotel_name'],
            ];

            // Handle venue restrictions if this is a venue manager referral
            if (
                $this->referral->referrer_type === 'venue_manager' &&
                ($venueGroup = $this->referral->referrer?->currentVenueGroup())
            ) {
                $conciergeData['venue_group_id'] = $venueGroup->id;

                $allowedVenueIds = $venueGroup->getAllowedVenueIds($this->referral->referrer);
                if (! empty($allowedVenueIds)) {
                    $conciergeData['allowed_venue_ids'] = array_map('intval', $allowedVenueIds);
                } else {
                    $conciergeData['allowed_venue_ids'] = [];
                    Log::info("Venue manager {$this->referral->referrer_id} has no specific allowed venues in group {$venueGroup->id}. Referred concierge {$user->id} granted access to none initially.");
                }
            } elseif ($this->referral->referrer_type === 'venue_manager') {
                Log::error("Could not determine venue group for venue manager referral: Referral ID {$this->referral->id}, Referrer ID {$this->referral->referrer_id}");
            }

            $user->concierge()->create($conciergeData);

            $user->notify(new ConciergeRegisteredEmail(
                sendAgreementCopy: $data['send_agreement_copy'] ?? false,
                userData: [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'date' => now()->format('F j, Y'),
                ]
            ));

            $user->notify(new ConciergeCreated($user));

            DB::commit();

            Auth::login($user);
            $this->redirect(config('app.platform_url'));
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to secure concierge account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'referral_id' => $this->referral->id ?? null,
            ]);

            Notification::make()
                ->title('Failed to create your account')
                ->body('There was an unexpected error. Please try again or contact support.')
                ->danger()
                ->send();
        }
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
