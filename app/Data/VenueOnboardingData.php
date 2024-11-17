<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class VenueOnboardingData extends Data
{
    public function __construct(
        #[Required]
        #[Max(255)]
        public string $company_name,

        #[Required]
        #[Max(255)]
        public string $first_name,

        #[Required]
        #[Max(255)]
        public string $last_name,

        #[Required]
        #[Max(255)]
        public string $email,

        #[Required]
        public string $phone,

        #[Required]
        #[Min(1)]
        public int $venue_count,

        #[Required]
        public array $venue_names,

        #[Required]
        public bool $has_logos,

        public ?array $logo_files,

        #[Required]
        public bool $agreement_accepted,

        #[Required]
        public array $prime_hours,

        #[Required]
        public bool $use_non_prime_incentive,

        #[Min(0)]
        public ?float $non_prime_per_diem,

        public bool $send_agreement_copy,
    ) {}
}
