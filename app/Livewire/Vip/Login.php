<?php

namespace App\Livewire\Vip;

use App\Services\VipAuthenticationService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Login extends Component implements HasForms
{
    use InteractsWithForms;

    private VipAuthenticationService $authService;

    public ?string $name = null;

    protected string $heading = 'Vip Login';

    public string $code = '';

    public string $message;

    public function boot(VipAuthenticationService $authService): void
    {
        $this->authService = $authService;
    }

    public function getHeading(): string
    {
        return $this->name ?? $this->heading;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->rules(['min:4', 'max:12', 'alpha_num'])
                    ->required(),
            ]);
    }

    public function mount(): void
    {
        if ($this->code) {
            $this->validateCode();
        }
        $this->message = $this->getErrorBag()->first('code') ?? '';
    }

    public function validateCode(): void
    {
        $this->validate();

        $vipCode = $this->authService->authenticate($this->code);

        if ($vipCode) {
            $this->handleSuccessfulAuthentication($vipCode);
        } else {
            $this->handleFailedAuthentication();
        }
    }

    private function handleSuccessfulAuthentication($vipCode): void
    {
        $this->authService->login($vipCode);
        $this->authService->setSessionData($vipCode);
        $this->redirectRoute('vip.booking');
    }

    private function handleFailedAuthentication(): void
    {
        $this->addError('code', 'The provided code is incorrect.');
        $this->message = $this->getErrorBag()->first('code');
    }

    public function render(): View
    {
        return view('livewire.vip-login');
    }
}
