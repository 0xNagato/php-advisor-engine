<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\VenueOnboardingData;
use App\Models\User;
use App\Models\VenueOnboarding as VenueOnboardingModel;
use App\Notifications\VenueAgreementCopy;
use App\Notifications\VenueOnboardingSubmitted;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Notification;

class VenueOnboarding extends Component
{
    use FormatsPhoneNumber;
    use WithFileUploads;

    /** @var array<string,string> */
    protected array $steps = [
        'company' => 'Company',
        'venues' => 'Venues',
        'prime-hours' => 'Hours',
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

    /** @var array<int, ?TemporaryUploadedFile> */
    public array $logo_files = [];

    public bool $agreement_accepted = false;

    /** @var array<int, array<string, array<string, bool>>> */
    public array $venue_prime_hours = [];

    public bool $use_non_prime_incentive = false;

    public ?float $non_prime_per_diem = null;

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

    public function mount(): void
    {
        // Load saved state from session if it exists
        if (Session::has('venue_onboarding')) {
            $savedState = Session::get('venue_onboarding');
            foreach ($savedState as $key => $value) {
                if (property_exists($this, $key)) {
                    if ($key === 'phone') {
                        // Format phone number when loading from session
                        $this->phone = $this->getInternationalFormattedPhoneNumber($value);
                    } else {
                        $this->$key = $value;
                    }
                }
            }
            // Explicitly set the step from session
            $this->step = $savedState['step'] ?? 'company';
        } else {
            $this->venue_names = array_fill(0, $this->venue_count, '');
            $this->step = 'company';
        }

        // Generate time slots in 30-minute increments from 11 AM to 11 PM
        $this->timeSlots = collect()
            ->range(0, 48) // 24 slots (12 hours * 2 slots per hour)
            ->map(function ($slot) {
                $hour = 11 + floor($slot / 2); // Start at 11:00 AM
                $minutes = ($slot % 2) * 30;

                return sprintf('%02d:%02d:00', $hour, $minutes);
            })
            ->filter(function ($time) {
                // Only keep times between 11 AM and 11 PM
                $hour = (int) substr($time, 0, 2);

                return $hour >= 11 && $hour < 23;
            })
            ->values()
            ->toArray();
    }

    public function updated($property): void
    {
        if ($property === 'phone') {
            $this->phone = $this->getInternationalFormattedPhoneNumber($this->phone);
        }

        // Save state to session whenever a property is updated
        Session::put('venue_onboarding', [
            'step' => $this->step,
            'company_name' => $this->company_name,
            'venue_count' => $this->venue_count,
            'venue_names' => $this->venue_names,
            'has_logos' => $this->has_logos,
            'agreement_accepted' => $this->agreement_accepted,
            'venue_prime_hours' => $this->venue_prime_hours,
            'use_non_prime_incentive' => $this->use_non_prime_incentive,
            'non_prime_per_diem' => $this->non_prime_per_diem,
            'send_agreement_copy' => $this->send_agreement_copy,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone, // Phone is already formatted at this point
        ]);
    }

    public function updatedVenueCount(): void
    {
        if ($this->venue_count > count($this->venue_names)) {
            $this->venue_names = array_pad($this->venue_names, $this->venue_count, '');
            // Initialize prime hours for new venues
            for ($i = count($this->venue_prime_hours); $i < $this->venue_count; $i++) {
                $this->venue_prime_hours[$i] = [];
            }
        } else {
            $this->venue_names = array_slice($this->venue_names, 0, $this->venue_count);
            $this->venue_prime_hours = array_slice($this->venue_prime_hours, 0, $this->venue_count);
        }

        $this->logo_files = array_fill(0, $this->venue_count, null);
    }

    public function nextStep(): void
    {
        $this->validateStep();

        $this->step = match ($this->step) {
            'company' => 'venues',
            'venues' => 'prime-hours',
            'prime-hours' => 'incentive',
            'incentive' => 'agreement',
            default => $this->step
        };

        // Update session with new step after validation passes
        Session::put('venue_onboarding', array_merge(
            Session::get('venue_onboarding', []),
            ['step' => $this->step]
        ));
    }

    public function submit(): void
    {
        $this->validateStep();

        DB::transaction(function (): void {
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
                'venue_prime_hours' => $this->venue_prime_hours,
                'use_non_prime_incentive' => $this->use_non_prime_incentive,
                'non_prime_per_diem' => $this->non_prime_per_diem,
                'send_agreement_copy' => $this->send_agreement_copy,
            ]);

            $onboarding = VenueOnboardingModel::create([
                ...$data->toArray(),
                'status' => 'submitted',
            ]);

            foreach ($this->venue_names as $index => $name) {
                $location = $onboarding->locations()->create([
                    'name' => $name,
                    'prime_hours' => $this->venue_prime_hours[$index] ?? [],
                ]);

                if ($this->has_logos && isset($this->logo_files[$index])) {
                    $path = $this->logo_files[$index]->store('venue-logos', 'public');
                    $location->update(['logo_path' => $path]);
                }
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
        });

        // Clear the session after successful submission
        Session::forget('venue_onboarding');
        $this->submitted = true;
    }

    private function validateStep(): void
    {
        match ($this->step) {
            'company' => $this->validate([
                'company_name' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $phone = phone($value, config('app.countries')[0]);
                        if (! $phone->isValid()) {
                            $fail('The phone number is invalid.');
                        }
                    },
                ],
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
                'use_non_prime_incentive' => 'required|boolean',
                'non_prime_per_diem' => 'nullable|numeric|min:0|required_if:use_non_prime_incentive,true',
            ]),
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.venue-onboarding')
            ->layout('components.layouts.app', [
                'title' => $this->submitted ? 'Onboarding Submitted' : 'Venue Onboarding',
            ]);
    }
}
