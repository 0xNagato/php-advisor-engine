<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\PasswordResetResponse;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * @property User $user
 */
class ResetPassword extends \Filament\Pages\Auth\PasswordReset\ResetPassword
{
    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'filament.pages.auth.reset-password';

    public string $first_name = '';

    public string $last_name = '';

    public string $hotel_name = '';

    #[Url(as: 'email')]
    public ?string $emailQuery = null;

    public bool $termsAndConditions = false;

    public function getTitle(): string|Htmlable
    {
        return 'Secure Your Account';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Secure Your Account';
    }

    public function form(Form $form): Form
    {
        $formSchema = [
            $this->getEmailFormComponent()
                ->hiddenLabel()
                ->columnSpan(2),
            $this->getPasswordFormComponent()
                ->hiddenLabel()
                ->placeholder('Password')
                ->columnSpan(2),
            $this->getPasswordConfirmationFormComponent()
                ->hiddenLabel()
                ->placeholder('Confirm Password')
                ->columnSpan(2),
            $this->getTermsAndConditionsFormComponent()
                ->columnSpan(2),
        ];

        // Add fields for first_name, last_name, and hotel_name if they are blank.
        if (blank($this->user->first_name) && blank($this->user->last_name)) {
            array_unshift(
                $formSchema,
                TextInput::make('first_name')
                    ->hiddenLabel()
                    ->placeholder('First Name')
                    ->dehydrated(false)
                    ->required(),
                TextInput::make('last_name')
                    ->hiddenLabel()
                    ->placeholder('Last Name')
                    ->dehydrated(false)
                    ->required(),
                TextInput::make('hotel_name')
                    ->label('Affiliation')
                    ->hiddenLabel()
                    ->placeholder('Hotel Name or Company Name')
                    ->columnSpan(2)
                    ->dehydrated(false)
                    ->required()
            );
        }

        return $form
            ->schema($formSchema)
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ]);
    }

    protected function getTermsAndConditionsFormComponent(): Placeholder
    {
        if ($this->user === null) {
            return Placeholder::make('termsAndConditions')->hiddenLabel();
        }

        $role = $this->user->main_role;
        $lowercaseRole = strtolower((string) $role);

        $label = new HtmlString("
            <div class='font-bold text-center text-indigo-800 underline cursor-pointer' x-data='{}' @click='\$dispatch(\"open-modal\", { id: \"$lowercaseRole-modal\" })'>
                Secure Your Account to Accept PRIMA $role Terms and Conditions
            </div>
        ");

        return Placeholder::make('termsAndConditions')
            ->content($label)
            ->hiddenLabel()
            ->hidden(fn () => ! (blank($this->user->secured_at) && in_array($lowercaseRole, ['concierge', 'venue'])));
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        $response = parent::resetPassword();

        $user = User::query()->where('email', $this->email)->firstOrFail();

        if ($user->secured_at === null) {
            $user->secured_at = now();
            $user->save();
        }

        if (blank($this->user->first_name) && blank($this->user->last_name)) {
            $user->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
            ]);

            $user->concierge()->update([
                'hotel_name' => $this->hotel_name,
            ]);
        }

        return $response;
    }

    public function getResetPasswordFormAction(): Action
    {
        return Action::make('resetPassword')
            ->label('Secure Your Account')
            ->submit('resetPassword');
    }

    #[Computed]
    public function user(): User
    {
        return User::query()->where('email', $this->emailQuery)->firstOrFail();
    }
}
