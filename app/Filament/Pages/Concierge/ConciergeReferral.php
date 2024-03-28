<?php

namespace App\Filament\Pages\Concierge;

use App\Events\ConciergeReferred;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * @property Form $form
 */
class ConciergeReferral extends Page
{
    protected static ?string $navigationIcon = 'gmdi-people-alt-tt';

    protected static string $view = 'filament.pages.concierge.concierge-referral';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'My Referrals';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
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
            ->statePath('data');
    }

    public function sendInvite(): void
    {
        $data = $this->form->getState();
        
        $user = User::create([
            'first_name' => '',
            'last_name' => '',
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => bcrypt(Str::random(8)),
            'concierge_referral_id' => auth()->user()->concierge->id,
        ]);

        $user->assignRole('concierge');

        $user->concierge()->create([
            'hotel_name' => '',
        ]);

        $this->form->fill();

        ConciergeReferred::dispatch($user);

        $this->dispatch('concierge-referred');

        Notification::make()
            ->title('Invite sent successfully.')
            ->success()
            ->send();

    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
    }
}
