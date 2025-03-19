<?php

namespace App\Filament\Pages\Admin;

use App\Models\BookingModificationRequest;
use Filament\Pages\Page;

class ConfirmationManager extends Page
{
    protected static ?string $navigationIcon = 'polaris-transaction-icon';

    protected static string $view = 'filament.pages.admin.confirmation-manager';

    public ?int $modificationRequestsCount = 0;

    public function mount(): void
    {
        $this->modificationRequestsCount = BookingModificationRequest::query()
            ->where('status', BookingModificationRequest::STATUS_PENDING)->count();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole('super_admin');
    }
}
