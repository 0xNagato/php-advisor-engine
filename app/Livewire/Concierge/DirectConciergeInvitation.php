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

    public function mount(string $type, string $id): void
    {
        abort_unless(boolean: request()?->hasValidSignature(), code: 401);

        try {
            match ($type) {
                'partner' => $this->invitingPartner = Partner::query()->findOrFail($id),
                'concierge' => $this->invitingConcierge = Concierge::query()->findOrFail($id),
                'venue_manager' => $this->invitingVenueManager = User::query()->findOrFail($id),
                default => abort(404, "Invalid type: {$type}"),
            };

            if ($type === 'venue_manager' && ! $this->invitingVenueManager?->hasActiveRole('venue_manager')) {
                Log::warning("User {$id} accessed venue manager referral link but does not have the role.");
            }

        } catch (Exception $e) {
            abort(404, $e->getMessage());
        }

        $defaultRegion = match ($type) {
            'partner' => $this->invitingPartner?->user?->region,
            'concierge' => $this->invitingConcierge?->user?->region,
            'venue_manager' => $this->invitingVenueManager?->region,
            default => null,
        } ?? config('app.default_region');

        $this->form->fill([
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'notification_regions' => [$defaultRegion],
            'hotel_name' => '',
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
