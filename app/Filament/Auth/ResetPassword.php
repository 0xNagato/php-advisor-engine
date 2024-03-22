<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ResetPassword extends \Filament\Pages\Auth\PasswordReset\ResetPassword
{
    protected static string $layout = 'components.layouts.app';

    protected static string $view = 'filament.pages.auth.reset-password';

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
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getTermsAndConditionsFormComponent()
        ];

        return $form->schema($formSchema);
    }

    protected function getTermsAndConditionsFormComponent(): Placeholder
    {
        $email = request()?->query('email');
        $user = User::where('email', $email)->first();

        if ($user === null) {
            return Placeholder::make('termsAndConditions')->hiddenLabel();
        }

        $role = $user->main_role;
        $lowercaseRole = strtolower($role);

        $label = new HtmlString("
            <div class='text-indigo-800 underline' x-data='{}' @click='\$dispatch(\"open-modal\", { id: \"$lowercaseRole-modal\" })'>
                Terms and Conditions of PRIMA $role Program
            </div>
        ");

        return Placeholder::make('termsAndConditions')
            ->content($label)
            ->hiddenLabel()
            ->hidden(function () use ($user, $lowercaseRole) {
                return !($user && empty($user->secured_at) && in_array($lowercaseRole, ['concierge', 'restaurant']));
            });
    }

    public function getResetPasswordFormAction(): Action
    {
        return Action::make('resetPassword')
            ->label('Secure Your Account')
            ->submit('resetPassword');
    }
}
