<?php

namespace App\Filament\Pages;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use HasanAhani\FilamentOtpInput\Components\OtpInput;
use Illuminate\Contracts\View\View;

class TwoFactorCode extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.twofactorcode';

    protected static ?string $title = 'Two Factor Authentication';

    protected ?string $heading = 'Two Factor Authentication';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public int $tries = 0;

    public int $regenerate = 0;

    public string $phoneNumber;

    public string $redirectRoute;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                OtpInput::make('code')
                    ->numberInput(6)
                    ->required()
                    ->label('Code'),
            ])->statePath('data');
    }

    public function mount()
    {
        $sessionKey = 'usercode.'.auth()->id();

        // if this device is verified, redirect to the dashboard
        if (session()->has($sessionKey) && session($sessionKey) === true) {
            return redirect()->route('filament.admin.pages.admin-dashboard');
        }

        // hide the navigation for the 2FA page
        Filament::getPanel()
            ->navigation(false);

        // send the code to the user
        auth()->user()->generateCode();

        $this->phoneNumber = substr(auth()->user()->phone, -4);

        $this->redirectRoute = request()->query('redirect');
    }

    public function save()
    {
        $code = $this->form->getState()['code'];

        if (! auth()->user()->verify2FACode($code)) {
            $this->tries++;

            if ($this->tries >= 3) {
                Filament::auth()->logout();
                session()->invalidate();
                session()->regenerateToken();

                return redirect()->route('filament.admin.auth.login');
            }

            $this->addError('data.code', 'The provided 2FA code is incorrect.');
            $this->reset('data');
        } else {
            auth()->user()->markDeviceAsVerified();

            // Update the user with the pending data
            $sessionKey = 'pending-data.'.auth()->id();
            $pendingData = session()->get($sessionKey);

            Notification::make()
                ->title('Changes updated successfully.')
                ->success()
                ->send();

            // Check if there is pending data to update
            if ($pendingData) {
                auth()->user()->update($pendingData);

                session()->forget($sessionKey);

                return redirect()->route($this->redirectRoute);
            }

            return redirect()->route('filament.admin.pages.admin-dashboard');
        }
    }

    public function regenerateCode()
    {
        $this->regenerate++;

        if ($this->regenerate >= 3) {
            Filament::auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login');
        }

        auth()->user()->generateCode();
        $this->reset('data');
        $this->tries = 0;
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.twofactorcode-header');
    }
}
