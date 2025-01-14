<?php

namespace App\Livewire\Concierge;

use App\Models\Concierge;
use App\Models\Partner;
use App\Traits\HandlesConciergeInvitation;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;

class DirectConciergeInvitation extends SimplePage
{
    use HandlesConciergeInvitation;
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'livewire.concierge-invitation';

    protected static string $layout = 'components.layouts.app';

    public function mount(string $type, string $id): void
    {
        abort_unless(boolean: request()?->hasValidSignature(), code: 401);

        try {
            match ($type) {
                'partner' => $this->invitingPartner = Partner::query()->findOrFail($id),
                'concierge' => $this->invitingConcierge = Concierge::query()->findOrFail($id),
                default => abort(404, "Invalid type: {$type}"),
            };
        } catch (Exception $e) {
            abort(404, $e->getMessage());
        }

        $this->form->fill([
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'notification_regions' => [
                $this->invitingPartner?->user?->region ??
                $this->invitingConcierge?->user?->region ??
                'miami',
            ],
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
