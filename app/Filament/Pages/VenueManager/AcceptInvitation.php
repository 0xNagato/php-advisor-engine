<?php

namespace App\Filament\Pages\VenueManager;

use App\Models\Referral;
use App\Models\User;
use App\Models\VenueGroup;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class AcceptInvitation extends SimplePage
{
    use InteractsWithForms;
    use WithRateLimiting;

    protected static string $view = 'filament.pages.venue-manager.accept-invitation';

    protected static string $layout = 'components.layouts.app';

    public ?Referral $referral = null;

    public ?string $invitationUsedMessage = null;

    public array $data = [];

    public function mount(Referral $referral): void
    {
        abort_unless(request()->hasValidSignature(), 401);
        abort_unless($referral->type === 'venue_manager', 403);

        if ($referral->secured_at || $referral->user_id) {
            $this->invitationUsedMessage = 'This invitation has already been claimed.';

            return;
        }

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
            ->columns([
                'default' => 2,
            ])
            ->schema([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique('users', 'email')
                    ->columnSpan(2),
                PhoneInput::make('phone')
                    ->required()
                    ->unique('users', 'phone')
                    ->columnSpan(2),
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->rule(Password::defaults())
                    ->confirmed()
                    ->columnSpan(2),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->columnSpan(2),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => bcrypt($data['password']),
            ]);

            $user->assignRole('venue_manager');

            $meta = $this->referral->meta;
            $venueGroup = VenueGroup::query()->findOrFail($meta['venue_group_id']);

            $venueGroup->managers()->attach($user->id, [
                'allowed_venue_ids' => json_encode($meta['allowed_venue_ids']),
                'current_venue_id' => $meta['allowed_venue_ids'][0] ?? null,
            ]);

            $this->referral->update([
                'user_id' => $user->id,
                'secured_at' => now(),
            ]);

            auth()->login($user);

            $this->redirect(VenueManagerDashboard::getUrl());
        });

        Notification::make()
            ->success()
            ->title('Account created successfully')
            ->send();

        $this->redirect(VenueManagerDashboard::getUrl());
    }
}
