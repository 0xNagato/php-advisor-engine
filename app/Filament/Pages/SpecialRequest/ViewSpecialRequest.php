<?php

namespace App\Filament\Pages\SpecialRequest;

use App\Models\SpecialRequest;
use App\Models\User;
use App\Traits\SpecialRequest\UseSpecialRequestFormatting;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class ViewSpecialRequest extends Page
{
    use UseSpecialRequestFormatting;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.special-request.view-special-request';

    public ?string $heading = '';

    protected static ?string $slug = '/special-requests/{specialRequest?}';

    protected static bool $shouldRegisterNavigation = false;

    public SpecialRequest $specialRequest;

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->hasActiveRole(['concierge', 'super_admin']) || $user->hasActiveRole('venue');
    }

    public function mount(SpecialRequest $specialRequest): void
    {
        $this->specialRequest = $specialRequest;
        $this->authorize('view', $specialRequest);
    }

    #[Computed]
    public function venueTotalFee(): float
    {
        return ($this->commissionRequestedPercentage() / 100) * $this->minimumSpend();
    }

    #[Computed]
    public function minimumSpend(): int
    {
        return (int) str_replace(',', '', $this->specialRequest->minimum_spend);
    }

    #[Computed]
    public function commissionRequestedPercentage(): float
    {
        return $this->specialRequest->commission_requested_percentage;
    }
}
