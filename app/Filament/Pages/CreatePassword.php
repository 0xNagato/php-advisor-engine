<?php

namespace App\Filament\Pages;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CreatePassword extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'filament.pages.create-password';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public ?User $user = null;

    public function mount(string $token)
    {
        abort_unless(request()->hasValidSignature(), 401);

        if (auth()->check()) {
            return redirect(config('app.platform_url'));
        }

        try {
            $userId = decrypt($token);

            // First check if user exists
            $user = User::query()
                ->where('id', $userId)
                ->first();

            abort_unless($user, 404, 'User not found');

            // Now check if they've already secured their account
            if ($user->secured_at !== null) {
                // For Filament v3, we need to use a special version of notifications
                // that works with non-Livewire redirects
                session()->flash('filament.notifications', [
                    [
                        'id' => uniqid(),
                        'type' => 'info',
                        'title' => 'Account Already Secured',
                        'body' => 'Your account has already been secured. Please log in with your email and password.',
                        'duration' => 5000,
                    ],
                ]);

                return redirect()->route('filament.admin.auth.login');
            }

            // Only assign to $this->user if they haven't secured their account
            $this->user = $user;
        } catch (Exception) {
            abort(401, 'Invalid token');
        }
    }

    public function getHeading(): string
    {
        return 'Create your password';
    }

    public function getSubheading(): string
    {
        return 'Please set a secure password for your account.';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::defaults())
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->addError('password', "Too many attempts. Please wait {$exception->secondsUntilAvailable} seconds.");

            return;
        }

        $validated = $this->form->getState();

        $this->user->update([
            'password' => Hash::make($validated['password']),
            'secured_at' => now(),
        ]);

        auth()->login($this->user);

        $this->redirect(config('app.platform_url'));
    }
}
