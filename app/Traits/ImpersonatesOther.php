<?php

namespace App\Traits;

use Filament\Facades\Filament;
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
        return Filament::getCurrentPanel()->getAuthGuard();
    }

    public function getRedirectTo(): ?string
    {
        return config('app.platform_url');
    }
}
