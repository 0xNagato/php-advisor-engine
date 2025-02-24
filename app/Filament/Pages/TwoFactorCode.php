<?php

namespace App\Filament\Pages;

use App\Services\TwoFactorAuthenticationService;
use Carbon\Carbon;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use HasanAhani\FilamentOtpInput\Components\OtpInput;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportRedirects\Redirector;
use Throwable;

class TwoFactorCode extends Page
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.pages.twofactorcode';

    protected static ?string $title = 'Two Factor Authentication';

    protected ?string $heading = 'Two Factor Authentication';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public int $tries = 0;

    public int $regenerate = 0;

    public string $phoneNumberSuffix;

    public string $redirectUrl;

    protected ?TwoFactorAuthenticationService $twoFactorService = null;

    protected ?Request $request = null;

    public ?Carbon $nextCodeAvailableAt = null;

    public bool $codeSent = false;

    public bool $formVisible = false;

    public function boot(TwoFactorAuthenticationService $twoFactorService, Request $request): void
    {
        $this->twoFactorService = $twoFactorService;
        $this->request = $request;
    }

    /**
     * @throws Throwable
     */
    public function mount(): void
    {
        Filament::getPanel()->navigation(false);
        $this->phoneNumberSuffix = substr((string) auth()->user()->phone, -4);
        $this->redirectUrl = $this->request->query('redirect') ?? config('app.platform_url');

        $this->nextCodeAvailableAt = $this->twoFactorService->getNextCodeAvailableAt(auth()->user());
        $this->formVisible = $this->codeWasSent;
    }

    /**
     * @throws Throwable
     */
    public function sendCode(): void
    {
        $this->generateNewCode();
        $this->formVisible = true;
    }

    /**
     * @throws Throwable
     */
    public function generateNewCode(): void
    {
        $code = $this->twoFactorService->generateCode(auth()->user());

        if ($code === null) {
            $this->nextCodeAvailableAt = $this->twoFactorService->getNextCodeAvailableAt(auth()->user());
        } else {
            $this->nextCodeAvailableAt = Carbon::now()->addMinutes(2);
        }

        $this->reset('data');
    }

    #[Computed]
    public function canResendCode(): bool
    {
        return $this->nextCodeAvailableAt === null || $this->nextCodeAvailableAt->isPast();
    }

    #[Computed]
    public function formattedNextCodeAvailableAt(): string
    {
        if (! $this->nextCodeAvailableAt || $this->nextCodeAvailableAt->isPast()) {
            return '';
        }

        return $this->nextCodeAvailableAt
            ->setTimezone(auth()->user()->timezone)
            ->format('g:i A');
    }

    #[Computed]
    public function codeWasSent(): bool
    {
        return $this->nextCodeAvailableAt !== null && $this->nextCodeAvailableAt->isFuture();
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.twofactorcode-header');
    }

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

    /**
     * @throws Throwable
     */
    public function save(): RedirectResponse|Redirector|null
    {
        $code = $this->form->getState()['code'];

        if (! $this->twoFactorService->verifyCode(auth()->user(), $code)) {
            return $this->handleIncorrectCode();
        }

        return $this->handleCorrectCode();
    }

    /**
     * @throws Throwable
     */
    private function handleIncorrectCode(): RedirectResponse|Redirector|null
    {
        $this->tries++;

        if ($this->tries >= 3) {
            return $this->handleTooManyAttempts();
        }

        $this->addError('data.code', 'The provided 2FA code is incorrect.');
        $this->reset('data');

        return null;
    }

    private function handleCorrectCode(): RedirectResponse|Redirector
    {
        $this->twoFactorService->markDeviceAsVerified(auth()->user(), $this->request);

        Notification::make()
            ->title('2FA code inputted successfully.')
            ->success()
            ->send();

        return redirect($this->redirectUrl);
    }

    private function handleTooManyAttempts(): RedirectResponse|Redirector
    {
        Filament::auth()->logout();
        Session::invalidate();
        Session::regenerateToken();

        return redirect()->route('filament.admin.auth.login');
    }
}
