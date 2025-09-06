<?php

namespace App\Livewire\Concierge;

use App\Models\Concierge;
use App\Models\Partner;
use App\Models\User;
use App\Traits\HandlesConciergeInvitation;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Log;

class DirectConciergeInvitation extends SimplePage
{
    use HandlesConciergeInvitation;
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'livewire.concierge-invitation';

    protected static string $layout = 'components.layouts.app';

    public ?User $invitingVenueManager = null;

    public ?string $qrCodeId = null;

    public function mount(string $type, ?string $id = null): void
    {
        // Check if this is a generic invitation route (no specific referrer)
        $isGenericRoute = request()->routeIs('join.generic') || ($type === 'concierge' && ! $id);

        // Generic invitations don't require signed URLs
        if ($isGenericRoute) {
            $this->qrCodeId = request()->get('qr');

            // Check if there's a referrer concierge ID in the request
            $referrerId = request()->get('referrer');
            if ($referrerId) {
                try {
                    $this->invitingConcierge = Concierge::query()->findOrFail($referrerId);
                } catch (\Exception $e) {
                    // Invalid referrer ID, ignore it
                }
            }

            // Initialize form data for generic route
            $this->initializeFormData();

            return;
        }

        abort_unless(boolean: request()?->hasValidSignature(), code: 401);

        // Process specific invitation types
        try {
            match ($type) {
                'partner' => $this->invitingPartner = Partner::query()->findOrFail($id),
                'concierge' => $this->invitingConcierge = Concierge::query()->findOrFail($id),
                'venue_manager' => $this->invitingVenueManager = User::query()->findOrFail($id),
                default => abort(404, "Invalid type: {$type}"),
            };
        } catch (Exception $e) {
            abort(404, $e->getMessage());
        }

        if ($type === 'venue_manager' && ! $this->invitingVenueManager?->hasActiveRole('venue_manager')) {
            Log::warning("User {$id} accessed venue manager referral link but does not have the role.");
        }

        // Capture QR code ID if present in the request
        $this->qrCodeId = request()->get('qr');

        $this->initializeFormData();
    }

    protected function initializeFormData(): void
    {
        $defaultRegion = match (true) {
            $this->invitingPartner !== null => $this->invitingPartner->user?->region,
            $this->invitingConcierge !== null => $this->invitingConcierge->user?->region,
            $this->invitingVenueManager !== null => $this->invitingVenueManager->region,
            default => null,
        } ?? config('app.default_region');

        $this->data = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'notification_regions' => [$defaultRegion],
            'hotel_name' => '',
            'password' => '',
            'passwordConfirmation' => '',
            'send_agreement_copy' => false,
        ];

        $this->form->fill($this->data);
    }

    /**
     * @return array<Action>
     */
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
