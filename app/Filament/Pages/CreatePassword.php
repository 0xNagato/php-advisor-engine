<?php

namespace App\Filament\Pages;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
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

        $this->user = User::query()
            ->whereNull('secured_at')
            ->where('id', decrypt($token))
            ->firstOrFail();
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
