<?php

namespace App\Livewire\Partner;

use App\Actions\Partner\InviteConciergeViaSms;
use App\Models\Referral;
use App\Models\Region;
use App\Models\User;
use App\Notifications\Concierge\NotifyConciergeReferral;
use App\Traits\FormatsPhoneNumber;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use libphonenumber\PhoneNumberType;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class ConciergeInvitationForms extends Widget implements HasForms
{
    use FormatsPhoneNumber;
    use InteractsWithForms;

    protected static string $view = 'livewire.partner.concierge-invitation-forms';

    public int|string|array $columnSpan = 'full';

    public ?array $smsData = [];

    public ?array $emailData = [];

    public string $activeTab = 'sms';

    public function mount(): void
    {
        $this->smsForm->fill();
        $this->emailForm->fill();
    }

    protected function getForms(): array
    {
        return [
            'smsForm',
            'emailForm',
        ];
    }

    private function getCommonFormFields(): array
    {
        return [
            TextInput::make('first_name')
                ->required()
                ->live()
                ->hiddenLabel()
                ->extraInputAttributes(['class' => 'text-sm'])
                ->placeholder('First Name'),
            TextInput::make('last_name')
                ->required()
                ->live()
                ->hiddenLabel()
                ->extraInputAttributes(['class' => 'text-sm'])
                ->placeholder('Last Name'),
            Select::make('region_id')
                ->label('Region')
                ->required()
                ->hiddenLabel()
                ->extraAttributes(['class' => 'text-sm'])
                ->extraInputAttributes(['class' => 'text-sm'])
                ->live()
                ->placeholder('Select Region')
                ->options(Region::query()->pluck('name', 'id'))
                ->searchable(),
            TextInput::make('company_name')
                ->live()
                ->hiddenLabel()
                ->extraInputAttributes(['class' => 'text-sm'])
                ->placeholder('Hotel/Company Name'),
        ];
    }

    public function smsForm(Form $form): Form
    {
        return $form
            ->extraAttributes(['class' => 'inline-form'])
            ->columns(['default' => 2])
            ->schema([
                ...$this->getCommonFormFields(),
                PhoneInput::make('phone')
                    ->required()
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
                    ->columnSpan(2)
                    ->hiddenLabel()
                    ->extraInputAttributes(['class' => 'text-sm']),
                Actions::make([
                    Action::make('sendText')
                        ->label('Send SMS')
                        ->action('sendInviteViaText')
                        ->disabled(fn (Get $get) => ! filled($get('first_name')) ||
                            ! filled($get('last_name')) ||
                            ! filled($get('region_id')) ||
                            ! filled($get('phone'))
                        ),
                ])->columnSpanFull()->fullWidth(),
            ])
            ->statePath('smsData');
    }

    public function emailForm(Form $form): Form
    {
        return $form
            ->extraAttributes(['class' => 'inline-form'])
            ->columns(['default' => 2])
            ->schema([
                ...$this->getCommonFormFields(),
                TextInput::make('email')
                    ->required()
                    ->live()
                    ->placeholder('Email Address')
                    ->unique(User::class, 'email')
                    ->type('email')
                    ->columnSpan(2)
                    ->hiddenLabel()
                    ->extraInputAttributes(['class' => 'text-sm']),
                Actions::make([
                    Action::make('sendEmail')
                        ->label('Send Email')
                        ->action('sendInviteViaEmail')
                        ->disabled(fn (Get $get) => ! filled($get('first_name')) ||
                            ! filled($get('last_name')) ||
                            ! filled($get('region_id')) ||
                            ! filled($get('email'))
                        ),
                ])->columnSpanFull()->fullWidth(),
            ])
            ->statePath('emailData');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function sendInviteViaText(): void
    {
        $data = $this->smsForm->getState();

        $referral = InviteConciergeViaSms::run($data);

        $this->dispatch('concierge-referred');

        Notification::make()
            ->title('Invite sent successfully.')
            ->success()
            ->send();

        $this->smsForm->fill();
    }

    public function sendInviteViaEmail(): void
    {
        $data = $this->emailForm->getState();

        $referral = Referral::query()->create([
            'referrer_id' => auth()->id(),
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'region_id' => $data['region_id'],
            'type' => 'concierge',
            'referrer_type' => strtolower(auth()->user()->main_role),
            'company_name' => $data['company_name'],
        ]);

        $referral->notify(new NotifyConciergeReferral(referral: $referral, channel: 'mail'));

        $this->dispatch('concierge-referred');

        Notification::make()
            ->title('Invite sent successfully.')
            ->success()
            ->send();

        $this->emailForm->fill();
    }
}
