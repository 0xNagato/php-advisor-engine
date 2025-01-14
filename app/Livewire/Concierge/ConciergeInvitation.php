<?php

namespace App\Livewire\Concierge;

use App\Models\Referral;
use App\Traits\HandlesConciergeInvitation;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;

class ConciergeInvitation extends SimplePage
{
    use HandlesConciergeInvitation;
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'livewire.concierge-invitation';

    protected static string $layout = 'components.layouts.app';

    public function mount(?Referral $referral = null): void
    {
        abort_unless(boolean: request()?->hasValidSignature(), code: 401);

        if (! $referral) {
            abort(404, 'Missing referral');
        }

        $this->handleExistingReferral($referral);
    }

    protected function handleExistingReferral(Referral $referral): void
    {
        $this->referral = $referral;

        if ($referral->secured_at || $referral->user_id) {
            $this->invitationUsedMessage = 'This invitation has already been claimed.';

            return;
        }

        $this->form->fill([
            'first_name' => $referral->first_name,
            'last_name' => $referral->last_name,
            'email' => $referral->email,
            'phone' => $referral->phone,
            'notification_regions' => $referral->region_id ? [$referral->region_id] : [],
            'hotel_name' => $referral->company_name,
        ]);
    }

    public function getFormActions(): array
    {
        return [
            Action::make('secureAccount')
                ->label(__('Create Your Account'))
                ->color('indigo')
                ->submit('secureAccount'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
