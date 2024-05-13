<?php

namespace App\Filament\Pages;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Enter2fa extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.enter2fa';

    protected static ?string $title = 'Two Factor Authentication';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public int $tries = 0;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->numeric()
                    ->length(6)
                    ->required(),
            ])->statePath('data');
    }

    public function mount()
    {
        $sessionKey = 'twofacode' . auth()->id();

        // if this device is verified, redirect to the dashboard
        if (session()->has($sessionKey) && session($sessionKey) === true) {
            return redirect()->route('filament.admin.pages.admin-dashboard');
        }

        // hide the navigation for the 2FA page
        Filament::getPanel()
            ->navigation(false);

        // send the code to the user
        auth()->user()->generateCode();
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

            $this->addError('code', 'The provided 2FA code is incorrect.');
            $this->reset('data');

        } else {
            auth()->user()->markDeviceAsVerified();

            return redirect()->route('filament.admin.pages.admin-dashboard');
        }
    }

    public function regenerateCode()
    {
        auth()->user()->generateCode();
        $this->tries = 0;
        $this->reset('data');
    }
}
