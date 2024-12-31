<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VenueOnboarding;
use Barryvdh\DomPDF\Facade\Pdf;
use Lorisleiva\Actions\Concerns\AsAction;

class GenerateVenueAgreement
{
    use AsAction;

    public function handle(VenueOnboarding $onboarding): string
    {
        $venue_names = $onboarding->locations->pluck('name')->toArray();

        $pdf = PDF::loadView('pdfs.venue-agreement', [
            'company_name' => $onboarding->company_name,
            'venue_names' => $venue_names,
            'first_name' => $onboarding->first_name,
            'last_name' => $onboarding->last_name,
            'use_non_prime_incentive' => $onboarding->use_non_prime_incentive,
            'non_prime_per_diem' => $onboarding->non_prime_per_diem,
        ]);

        return $pdf->output();
    }
}
