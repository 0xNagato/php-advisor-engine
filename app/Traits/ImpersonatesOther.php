<?php

namespace App\Traits;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;
use Livewire\Features\SupportRedirects\Redirector;
use STS\FilamentImpersonate\Concerns\Impersonates;

trait ImpersonatesOther
{
    use Impersonates;

    public function getBackTo(): ?string
    {
        return null;
    }

    public function getGuard(): ?string
    {
        return Filament::getCurrentPanel()?->getAuthGuard();
    }

    public function getRedirectTo(): ?string
    {
        return config('app.platform_url');
    }

    public function impersonateVenue($record, $venue): bool|Redirector|RedirectResponse
    {
        if (! $this->canBeImpersonated($record)) {
            return false;
        }

        session()->put([
            'impersonate.back_to' => $this->getBackTo() ?? request(
                'fingerprint.path',
                request()->header('referer')
            ) ?? Filament::getCurrentPanel()->getUrl(),
            'impersonate.guard' => $this->getGuard(),
            'impersonate.venue_id' => $venue->id,
        ]);

        app(ImpersonateManager::class)->take(
            Filament::auth()->user(),
            $record,
            $this->getGuard()
        );

        return redirect($this->getRedirectTo());
    }
}
