<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\VenueOnboardingData;
use App\Models\Region;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueOnboarding as VenueOnboardingModel;
use App\Notifications\VenueAgreementCopy;
use App\Notifications\VenueOnboardingSubmitted;
use App\Traits\FormatsPhoneNumber;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Notification;

class VenueOnboarding extends Component
{
    use FormatsPhoneNumber;
    use WithFileUploads;

    // Add properties to track existing account detection
    public bool $existingAccountDetected = false;

    public string $existingAccountType = '';

    public string $existingAccountIdentifier = '';

    // Flag to determine if this is an existing venue manager adding a new venue
    public bool $isExistingVenueManager = false;

    private const array DEFAULT_BOOKING_HOURS = [
        'monday' => ['start' => '11:00:00', 'end' => '22:00:00', 'closed' => false],
        'tuesday' => ['start' => '11:00:00', 'end' => '22:00:00', 'closed' => false],
        'wednesday' => ['start' => '11:00:00', 'end' => '22:00:00', 'closed' => false],
        'thursday' => ['start' => '11:00:00', 'end' => '22:00:00', 'closed' => false],
        'friday' => ['start' => '11:00:00', 'end' => '22:00:00', 'closed' => false],
        'saturday' => ['start' => '11:00:00', 'end' => '22:00:00', 'closed' => false],
        'sunday' => ['start' => '11:00:00', 'end' => '22:00:00', 'closed' => false],
    ];

    /** @var array<string,string> */
    public array $steps = [
        'company' => 'Company',
        'venues' => 'Venues',
        'booking-hours' => 'Hours',
        'prime-hours' => 'Prime',
        'incentive' => 'Incentives',
        'agreement' => 'Agreement',
    ];

    public bool $submitted = false;

    public string $step = 'company';

    #[Validate('required|string|max:255')]
    public string $company_name = '';

    #[Validate('required|integer|min:1')]
    public int $venue_count = 1;

    /** @var array<int, string> */
    public array $venue_names = [];

    public bool $has_logos = false;

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $logo_files = [];

    public bool $agreement_accepted = false;

    /** @var array<int, array<string, array<string, bool>>> */
    public array $venue_prime_hours = [];

    /** @var array<int, bool> */
    public array $venue_use_non_prime_incentive = [];

    /** @var array<int, ?float> */
    public array $venue_non_prime_per_diem = [];

    public bool $send_agreement_copy = false;

    #[Validate('required|string|max:255')]
    public string $first_name = '';

    #[Validate('required|string|max:255')]
    public string $last_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string')]
    public string $phone = '';

    /** @var array<string> */
    public array $timeSlots = [];

    public int $current_venue_index = 0;

    /** @var array<int, string> */
    public array $venue_regions = [];

    /** @var array<int, array{value: string, label: string}> */
    public array $availableRegions = [];

    #[Validate('required|exists:users,id')]
    public ?string $partner_id = null;

    /** Partner name for display */
    public ?string $partner_name = null;

    /** @var array<int, array<string, array<string, array{start: string, end: string, closed: bool}>>> */
    public array $venue_booking_hours = [];

    public function mount(?string $token = null): void
    {
        // Initialize available regions
        $this->availableRegions = Region::active()
            ->get()
            ->map(fn (Region $region) => [
                'value' => $region->id,
                'label' => $region->name,
            ])
            ->toArray();

        // Check if the user is a logged-in venue manager
        if (Auth::check() && Auth::user()->hasActiveRole('venue_manager')) {
            $this->isExistingVenueManager = true;
            $this->handleExistingVenueManager();
        }
        // Otherwise, continue with normal onboarding flow
        else {
            // Check if a token was provided in the route
            if ($token) {
                try {
                    // Decrypt the token to get the partner ID
                    $partnerId = Crypt::decrypt($token);
                    $this->validatePartnerById($partnerId);
                } catch (Exception $e) {
                    // If decryption fails, log it but continue without a partner
                    Log::warning('Failed to decrypt partner token: '.$e->getMessage());
                }
            }
            // For backward compatibility, also check query params
            elseif (request()->has('partner_id')) {
                $partnerId = request()->get('partner_id');
                $this->validatePartnerById($partnerId);
            }
        }

        // Load saved state from session if it exists
        if (Session::has('venue_onboarding')) {
            $savedState = Session::get('venue_onboarding');
            foreach ($savedState as $key => $value) {
                if (property_exists($this, $key)) {
                    if ($key === 'phone') {
                        $this->phone = $this->getInternationalFormattedPhoneNumber($value);
                    } else {
                        $this->$key = $value;
                    }
                }
            }
            $this->step = $savedState['step'] ?? 'company';
        } else {
            $this->venue_names = array_fill(0, $this->venue_count, '');
            $this->venue_prime_hours = array_fill(0, $this->venue_count, []);
            $this->venue_use_non_prime_incentive = array_fill(0, $this->venue_count, true);
            $this->venue_non_prime_per_diem = array_fill(0, $this->venue_count, 10.0);
            $this->logo_files = array_fill(0, $this->venue_count, null);
            $this->venue_regions = array_fill(0, $this->venue_count, 'miami');

            // Skip directly to venues step for existing venue managers
            $this->step = $this->isExistingVenueManager ? 'venues' : 'company';
        }

        $this->initializeBookingHours();

        // Generate time slots in 30-minute increments from 11 AM to 11 PM
        $this->timeSlots = collect()
            ->range(0, 48)
            ->map(function ($slot) {
                $hour = 11 + floor($slot / 2);
                $minutes = ($slot % 2) * 30;

                return sprintf('%02d:%02d:00', $hour, $minutes);
            })
            ->filter(function ($time) {
                $hour = (int) substr($time, 0, 2);

                return $hour >= 11 && $hour < 23;
            })
            ->values()
            ->toArray();
    }

    /**
     * Validate a partner ID and set partner properties if valid
     */
    private function validatePartnerById(string $partnerId): void
    {
        // Verify this is a valid partner
        $partner = User::query()
            ->whereHas('roles', function (\Illuminate\Contracts\Database\Query\Builder $query) {
                $query->where('name', 'partner');
            })
            ->where('id', $partnerId)
            ->first();

        if ($partner) {
            $this->partner_id = (string) $partner->id; // Cast to string to avoid type issues
            $this->partner_name = "{$partner->first_name} {$partner->last_name}";
        }
    }

    private function initializeBookingHours(): void
    {
        if (blank($this->venue_booking_hours)) {
            $this->venue_booking_hours = array_fill(0, $this->venue_count, self::DEFAULT_BOOKING_HOURS);

            return;
        }

        // Ensure all venues have booking hours
        for ($i = 0; $i < $this->venue_count; $i++) {
            if (! isset($this->venue_booking_hours[$i])) {
                $this->venue_booking_hours[$i] = self::DEFAULT_BOOKING_HOURS;

                continue;
            }

            // Ensure all days are set for each venue
            foreach (array_keys(self::DEFAULT_BOOKING_HOURS) as $day) {
                if (! isset($this->venue_booking_hours[$i][$day])) {
                    $this->venue_booking_hours[$i][$day] = self::DEFAULT_BOOKING_HOURS[$day];
                }
            }
        }
    }

    public function updated($property): void
    {
        if ($property === 'phone') {
            $this->phone = $this->getInternationalFormattedPhoneNumber($this->phone);

            // Clear the existing account detection if the phone is changed
            if ($this->existingAccountDetected && $this->existingAccountType === 'phone') {
                $this->existingAccountDetected = false;
                $this->existingAccountType = '';
                $this->existingAccountIdentifier = '';
            }
        }

        if ($property === 'email' && $this->existingAccountDetected && $this->existingAccountType === 'email') {
            $this->existingAccountDetected = false;
            $this->existingAccountType = '';
            $this->existingAccountIdentifier = '';
        }

        // Save state to session whenever a property is updated
        Session::put('venue_onboarding', [
            'step' => $this->step,
            'company_name' => $this->company_name,
            'venue_count' => $this->venue_count,
            'venue_names' => $this->venue_names,
            'venue_regions' => $this->venue_regions,
            'has_logos' => $this->has_logos,
            'agreement_accepted' => $this->agreement_accepted,
            'venue_prime_hours' => $this->venue_prime_hours,
            'venue_use_non_prime_incentive' => $this->venue_use_non_prime_incentive,
            'venue_non_prime_per_diem' => $this->venue_non_prime_per_diem,
            'send_agreement_copy' => $this->send_agreement_copy,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'partner_id' => $this->partner_id,
            'venue_booking_hours' => $this->venue_booking_hours,
            'existingAccountDetected' => $this->existingAccountDetected,
            'existingAccountType' => $this->existingAccountType,
            'existingAccountIdentifier' => $this->existingAccountIdentifier,
        ]);
    }

    public function updatedVenueCount(): void
    {
        if ($this->venue_count > count($this->venue_names)) {
            $this->venue_names = array_pad($this->venue_names, $this->venue_count, '');
            $this->venue_prime_hours = array_pad($this->venue_prime_hours, $this->venue_count, []);
            $this->venue_use_non_prime_incentive = array_pad($this->venue_use_non_prime_incentive, $this->venue_count, true);
            $this->venue_non_prime_per_diem = array_pad($this->venue_non_prime_per_diem, $this->venue_count, 10.0);
            $this->venue_regions = array_pad($this->venue_regions, $this->venue_count, 'miami');
            $this->venue_booking_hours = array_pad($this->venue_booking_hours, $this->venue_count, self::DEFAULT_BOOKING_HOURS);
        } else {
            $this->venue_names = array_slice($this->venue_names, 0, $this->venue_count);
            $this->venue_prime_hours = array_slice($this->venue_prime_hours, 0, $this->venue_count);
            $this->venue_use_non_prime_incentive = array_slice($this->venue_use_non_prime_incentive, 0, $this->venue_count);
            $this->venue_non_prime_per_diem = array_slice($this->venue_non_prime_per_diem, 0, $this->venue_count);
            $this->venue_regions = array_slice($this->venue_regions, 0, $this->venue_count);
            $this->venue_booking_hours = array_slice($this->venue_booking_hours, 0, $this->venue_count);
        }

        $this->logo_files = array_fill(0, $this->venue_count, null);
    }

    public function nextStep(): void
    {
        // If an existing account is detected, don't validate or proceed
        if ($this->existingAccountDetected) {
            return;
        }

        $this->validateStep();

        if ($this->step === 'booking-hours' || $this->step === 'prime-hours' || $this->step === 'incentive') {
            if ($this->current_venue_index < count($this->venue_names) - 1) {
                $this->current_venue_index++;

                return;
            }
        }

        $this->step = match ($this->step) {
            'company' => 'venues',
            'venues' => 'booking-hours',
            'booking-hours' => 'prime-hours',
            'prime-hours' => 'incentive',
            'incentive' => 'agreement',
            default => $this->step
        };

        $this->current_venue_index = 0;

        Session::put('venue_onboarding', array_merge(
            Session::get('venue_onboarding', []),
            ['step' => $this->step]
        ));
    }

    public function previousStep(): void
    {
        if (($this->step === 'prime-hours' || $this->step === 'incentive') && $this->current_venue_index > 0) {
            $this->current_venue_index--;

            return;
        }

        $steps = array_keys($this->steps);
        $currentIndex = array_search($this->step, $steps);

        if ($currentIndex > 0) {
            $previousStep = $steps[$currentIndex - 1];

            // Prevent existing venue managers from going back to the company step
            if ($this->isExistingVenueManager && $previousStep === 'company') {
                return;
            }

            $this->step = $previousStep;

            // Reset venue index when moving back
            if ($previousStep === 'incentive' || $previousStep === 'prime-hours') {
                $this->current_venue_index = count($this->venue_names) - 1;
            } else {
                $this->current_venue_index = 0;
            }

            // Update session state immediately
            Session::put('venue_onboarding', array_merge(
                Session::get('venue_onboarding', []),
                [
                    'step' => $this->step,
                    'current_venue_index' => $this->current_venue_index,
                ]
            ));
        }
    }

    public function submit(): void
    {
        $this->validateStep();

        DB::transaction(function (): void {
            if ($this->isExistingVenueManager) {
                $this->submitAsExistingVenueManager();
            } else {
                $this->submitAsNewVenueOwner();
            }
        });

        // Clear the session after successful submission
        Session::forget('venue_onboarding');
        $this->submitted = true;
    }

    /**
     * Handle submission for a new venue owner (original flow)
     */
    private function submitAsNewVenueOwner(): void
    {
        $data = VenueOnboardingData::from([
            'company_name' => $this->company_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'venue_count' => $this->venue_count,
            'venue_names' => $this->venue_names,
            'has_logos' => $this->has_logos,
            'logo_files' => null,
            'agreement_accepted' => $this->agreement_accepted,
            'send_agreement_copy' => $this->send_agreement_copy,
            'partner_id' => $this->partner_id,
            'venue_booking_hours' => $this->venue_booking_hours,
        ]);

        $onboarding = VenueOnboardingModel::query()->create([
            ...$data->toArray(),
            'status' => 'submitted',
        ]);

        foreach ($this->venue_names as $index => $name) {
            $location = $onboarding->locations()->create([
                'name' => $name,
                'region' => $this->venue_regions[$index],
                'prime_hours' => $this->venue_prime_hours[$index] ?? [],
                'booking_hours' => $this->venue_booking_hours[$index] ?? [],
                'use_non_prime_incentive' => $this->venue_use_non_prime_incentive[$index] ?? false,
                'non_prime_per_diem' => $this->venue_use_non_prime_incentive[$index] ?
                    $this->venue_non_prime_per_diem[$index] :
                    null,
                'logo_path' => $this->has_logos ? $this->storeLogo($index, $name) : null,
            ]);
        }

        if ($this->send_agreement_copy) {
            Notification::route('mail', $this->email)
                ->notify(new VenueAgreementCopy($onboarding));
        }

        User::query()->whereHas('roles', function (Builder $query) {
            $query->where('name', 'super_admin');
        })->each(function ($admin) use ($onboarding) {
            $admin->notify(new VenueOnboardingSubmitted($onboarding));
        });
    }

    /**
     * Handle submission for an existing venue manager adding a new venue
     */
    private function submitAsExistingVenueManager(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $venueGroup = $user->currentVenueGroup();

        throw_unless($venueGroup, new Exception('No venue group found for the current user'));

        $data = VenueOnboardingData::from([
            'company_name' => $this->company_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'venue_count' => $this->venue_count,
            'venue_names' => $this->venue_names,
            'has_logos' => $this->has_logos,
            'logo_files' => null,
            'agreement_accepted' => $this->agreement_accepted,
            'send_agreement_copy' => $this->send_agreement_copy,
            'partner_id' => $this->partner_id,
            'venue_booking_hours' => $this->venue_booking_hours,
            // Add a note that this was submitted by an existing venue manager
            'additional_notes' => 'Submitted by existing venue manager (ID: '.$user->id.') for venue group (ID: '.$venueGroup->id.')',
            'venue_group_id' => $venueGroup->id,
        ]);

        $onboarding = VenueOnboardingModel::query()->create([
            ...$data->toArray(),
            'status' => 'submitted',
            'venue_group_id' => $venueGroup->id,
        ]);

        foreach ($this->venue_names as $index => $name) {
            $location = $onboarding->locations()->create([
                'name' => $name,
                'region' => $this->venue_regions[$index],
                'prime_hours' => $this->venue_prime_hours[$index] ?? [],
                'booking_hours' => $this->venue_booking_hours[$index] ?? [],
                'use_non_prime_incentive' => $this->venue_use_non_prime_incentive[$index] ?? false,
                'non_prime_per_diem' => $this->venue_use_non_prime_incentive[$index] ?
                    $this->venue_non_prime_per_diem[$index] :
                    null,
                'logo_path' => $this->has_logos ? $this->storeLogo($index, $name) : null,
                'venue_group_id' => $venueGroup->id,
            ]);
        }

        // Send notification to the venue manager confirming their submission
        if ($this->send_agreement_copy) {
            Notification::route('mail', $this->email)
                ->notify(new VenueAgreementCopy($onboarding));
        }

        // Always notify admins
        User::query()->whereHas('roles', function (Builder $query) {
            $query->where('name', 'super_admin');
        })->each(function ($admin) use ($onboarding) {
            $admin->notify(new VenueOnboardingSubmitted($onboarding));
        });
    }

    protected function validateStep(): void
    {
        match ($this->step) {
            'company' => $this->validate([
                'company_name' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    function ($attribute, $value, $fail) {
                        // Skip uniqueness check for existing venue managers
                        if ($this->isExistingVenueManager) {
                            return;
                        }

                        $exists = User::query()->where('email', $value)->exists();
                        if ($exists) {
                            $this->existingAccountDetected = true;
                            $this->existingAccountType = 'email';
                            $this->existingAccountIdentifier = $value;
                            $fail('The email address is already registered with another account.');
                        }
                    },
                ],
                'partner_id' => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        // Skip partner validation for existing venue managers who may not need to reselect a partner
                        if ($this->isExistingVenueManager) {
                            return;
                        }
                    },
                ],
                'phone' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $phone = phone($value, config('app.countries')[0]);
                        if (! $phone->isValid()) {
                            $fail('The phone number is invalid.');
                        }

                        // Skip uniqueness check for existing venue managers
                        if ($this->isExistingVenueManager) {
                            return;
                        }

                        // Check for uniqueness of the phone number in the users table
                        $phoneE164 = $phone->formatE164();
                        $exists = User::query()->where('phone', $phoneE164)->exists();
                        if ($exists) {
                            $this->existingAccountDetected = true;
                            $this->existingAccountType = 'phone';
                            $this->existingAccountIdentifier = $phoneE164;
                            $fail('The phone number is already registered with another account.');
                        }
                    },
                ],
            ], [
                'partner_id.required' => 'The partner reference is missing. Please use the link provided by your PRIMA Partner.',
            ]),
            'partner' => $this->validate([
                'partner_id' => 'required|exists:users,id',
            ]),
            'venues' => $this->validate([
                'venue_count' => 'required|integer|min:1',
                'venue_names.*' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'has_logos' => 'required|boolean',
                'logo_files.*' => 'nullable|image|max:2048',
            ], [
                'venue_names.*' => 'Please enter a name for Venue :position',
            ]),
            'agreement' => $this->validate([
                'agreement_accepted' => 'required|accepted',
            ]),
            'prime-hours' => $this->validate([
                'venue_prime_hours' => 'present|array',
            ]),
            'incentive' => $this->validate([
                'venue_use_non_prime_incentive' => 'required|array',
                'venue_non_prime_per_diem' => 'nullable|array',
            ]),
            'booking-hours' => $this->validate([
                'venue_booking_hours' => 'required|array',
                'venue_booking_hours.*' => 'required|array',
                'venue_booking_hours.*.*.closed' => 'required|boolean',
                'venue_booking_hours.*.*.start' => [
                    'required_if:venue_booking_hours.*.*.closed,false',
                    function ($attribute, $value, $fail) {
                        try {
                            if ($value && ! preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                                $parts = explode('.', $attribute);
                                $this->venue_booking_hours[$parts[1]][$parts[2]]['start'] =
                                    $this->formatTimeToHis($value);
                            }
                        } catch (Exception) {
                            $fail('Invalid time format');
                        }
                    },
                ],
                'venue_booking_hours.*.*.end' => [
                    'required_if:venue_booking_hours.*.*.closed,false',
                    function ($attribute, $value, $fail) {
                        try {
                            if ($value && ! preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                                $parts = explode('.', $attribute);
                                $this->venue_booking_hours[$parts[1]][$parts[2]]['end'] =
                                    $this->formatTimeToHis($value);
                            }
                        } catch (Exception) {
                            $fail('Invalid time format');
                        }
                    },
                ],
            ], [
                'venue_booking_hours.*.*.start.required_if' => 'Opening time is required when venue is open',
                'venue_booking_hours.*.*.end.required_if' => 'Closing time is required when venue is open',
            ]),
            default => null,
        };
    }

    private function formatTimeToHis(string $time): string
    {
        // Handle various time formats and convert to H:i:s
        try {
            // Remove any AM/PM and convert to 24-hour format
            return Carbon::createFromFormat('h:i A', $time)->format('H:i:s');
        } catch (Exception) {
            try {
                // Try parsing as 24-hour format
                return Carbon::createFromFormat('H:i', $time)->format('H:i:s');
            } catch (Exception) {
                // If all else fails, try to parse the existing format
                return Carbon::parse($time)->format('H:i:s');
            }
        }
    }

    public function render(): View
    {
        $partners = null;
        $partnerId = $this->partner_id;
        $partnerName = $this->partner_name;

        $partners = User::query()
            ->select(['id', 'first_name', 'last_name'])
            ->whereHas('roles', fn (\Illuminate\Contracts\Database\Query\Builder $query) => $query->where('name', 'partner'))
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => "{$user->first_name} {$user->last_name}",
            ]);

        $timeSlots = [];
        if ($this->step === 'prime-hours') {
            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                $timeSlots[$day] = $this->getAvailableTimeSlots($day);
            }
        }

        return view('livewire.venue-onboarding', [
            'partners' => $partners,
            'partnerId' => $partnerId,
            'partnerName' => $partnerName,
            'availableTimeSlots' => $timeSlots,
        ])->layout('components.layouts.app', [
            'title' => $this->submitted ? 'Onboarding Submitted' : 'Venue Onboarding',
        ]);
    }

    public function resetForm(): void
    {
        Session::forget('venue_onboarding');
        $this->reset();
        $this->mount();
    }

    public function updatedHasLogos(bool $value): void
    {
        if (! $value) {
            $this->logo_files = array_fill(0, $this->venue_count, null);
        }
    }

    public function deleteUpload(array $content, int $index): void
    {
        if (blank($this->logo_files[$index])) {
            return;
        }

        $this->logo_files[$index] = null;
    }

    protected function storeLogo(int $index, string $venueName): ?string
    {
        if (blank($this->logo_files[$index])) {
            return null;
        }

        try {
            $file = $this->logo_files[$index];
            $region = Str::slug($this->venue_regions[$index]);
            $venue = Str::slug($venueName);
            $company = Str::slug($this->company_name);
            $random = Str::random(6);

            $fileName = implode('-', [
                $region,
                $venue,
                $company,
                $random,
            ]).'.'.$file->getClientOriginalExtension();

            $path = $file->storeAs(
                'onboarded_venues',
                $fileName,
                ['disk' => 'do']
            );

            Storage::disk('do')->setVisibility($path, 'public');

            return $path;
        } catch (Exception $e) {
            Log::error('Error storing file:', [
                'error' => $e->getMessage(),
                'file_info' => [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ],
            ]);

            return null;
        }
    }

    protected function getAvailableTimeSlots(string $day): array
    {
        // If the venue is closed on this day, return empty array
        if ($this->venue_booking_hours[$this->current_venue_index][$day]['closed']) {
            return [];
        }

        $startTime = Carbon::createFromFormat('H:i:s', $this->venue_booking_hours[$this->current_venue_index][$day]['start'] ?? sprintf('%02d:00:00', Venue::DEFAULT_START_HOUR));
        $endTime = Carbon::createFromFormat('H:i:s', $this->venue_booking_hours[$this->current_venue_index][$day]['end'] ?? sprintf('%02d:00:00', Venue::DEFAULT_END_HOUR));

        return collect()
            ->range(0, 48)
            ->map(function ($slot) {
                $hour = 11 + floor($slot / 2);
                $minutes = ($slot % 2) * 30;

                return sprintf('%02d:%02d:00', $hour, $minutes);
            })
            ->filter(function ($time) use ($startTime, $endTime) {
                $slotTime = Carbon::createFromFormat('H:i:s', $time);

                return $slotTime->greaterThanOrEqualTo($startTime) &&
                       $slotTime->lessThan($endTime);
            })
            ->values()
            ->toArray();
    }

    /**
     * Handle pre-filling data for existing venue managers
     */
    private function handleExistingVenueManager(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $venueGroup = $user->currentVenueGroup();

        if ($venueGroup) {
            $this->company_name = $venueGroup->name;
            $this->agreement_accepted = true; // They've already accepted agreement previously
        }

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';

        // Find their partner if it exists
        if ($user->partner_id) {
            $this->partner_id = $user->partner_id;
            $partner = User::query()->find($user->partner_id);
            if ($partner) {
                $this->partner_name = "{$partner->first_name} {$partner->last_name}";
            }
        }
    }
}
